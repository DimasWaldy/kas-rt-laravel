/*
 * Smart RW - Absensi Kegiatan RFID + Palang Pintu Servo (ESP32 30-Pin)
 *
 * Komponen & Pin Wiring (ESP32 DevKit 30-Pin):
 * ------------------------------------------------------------------
 * 1. RFID RC522 (WAJIB 3.3V! JANGAN colok ke VIN/5V):
 *    - VCC  -> 3V3
 *    - GND  -> GND
 *    - SDA  -> GPIO 5   (SS)
 *    - SCK  -> GPIO 18
 *    - MOSI -> GPIO 23
 *    - MISO -> GPIO 19
 *    - RST  -> GPIO 27  (Jangan GPIO 22 karena dipakai I2C SCL LCD)
 *    - IRQ  -> Tidak dihubungkan
 *
 * 2. LCD 16x2 I2C (PCF8574, auto-detect 0x27 / 0x3F):
 *    - VCC  -> VIN (5V)
 *    - GND  -> GND
 *    - SDA  -> GPIO 21
 *    - SCL  -> GPIO 22
 *
 * 3. Servo SG90 (Palang Pintu):
 *    - Merah (VCC)     -> VIN (5V) atau adaptor 5V eksternal (common GND!)
 *    - Coklat (GND)    -> GND
 *    - Oranye (Signal) -> GPIO 13
 *    * Catatan: Jika ESP32 sering restart saat servo bergerak, pakai adaptor
 *      5V >= 2A atau power 5V terpisah dengan GND digabung ke GND ESP32.
 *
 * 4. Buzzer Aktif:
 *    - Positif (+) -> GPIO 25
 *    - Negatif (-) -> GND
 *
 * 5. Tombol Setup Hotspot:
 *    - Gunakan tombol BOOT bawaan ESP32 (GPIO 0).
 *    - Tahan BOOT ~3 detik saat alat sudah menyala untuk membuka ulang
 *      Hotspot Pengaturan (SmartRW-RFID -> http://192.168.4.1).
 *
 * Library yang wajib diinstall di Arduino IDE (Library Manager):
 * ------------------------------------------------------------------
 * - MFRC522 (by GithubCommunity)
 * - LiquidCrystal I2C (by Frank de Brabander)
 * - ESP32Servo (by Kevin Harrington / John K. Bennett)
 * (WiFi, WebServer, DNSServer, Preferences, HTTPClient, WiFiClientSecure
 *  sudah bawaan board package "esp32 by Espressif Systems").
 */

#include <Arduino.h>
#include <DNSServer.h>
#include <ESP32Servo.h>
#include <HTTPClient.h>
#include <LiquidCrystal_I2C.h>
#include <MFRC522.h>
#include <Preferences.h>
#include <SPI.h>
#include <WebServer.h>
#include <WiFi.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <time.h>

// ======================= KONFIGURASI PIN =======================
constexpr uint8_t RC522_SS_PIN = 5;
constexpr uint8_t RC522_RST_PIN = 27;
constexpr uint8_t RC522_SCK_PIN = 18;
constexpr uint8_t RC522_MISO_PIN = 19;
constexpr uint8_t RC522_MOSI_PIN = 23;

constexpr uint8_t LCD_SDA_PIN = 21;
constexpr uint8_t LCD_SCL_PIN = 22;

constexpr uint8_t SERVO_PIN = 13;
constexpr uint8_t BUZZER_PIN = 25;
constexpr uint8_t SETUP_BTN_PIN = 0; // Tombol BOOT bawaan ESP32

// =================== KONFIGURASI PERILAKU ALAT =================
constexpr uint8_t BUZZER_ACTIVE_LEVEL = HIGH; // Ubah ke LOW jika modul buzzer active-low
constexpr int SERVO_CLOSE_ANGLE = 0;
constexpr int SERVO_OPEN_ANGLE = 90;
constexpr unsigned long SERVO_OPEN_MS = 3000;

// Set true jika warga yang sudah absen ("already_present") tetap boleh membuka palang
constexpr bool OPEN_SERVO_ON_ALREADY_PRESENT = false;

constexpr unsigned long ANY_TAP_COOLDOWN_MS = 1500;
constexpr unsigned long SAME_UID_COOLDOWN_MS = 4000;
constexpr unsigned long HOLD_SETUP_BTN_MS = 2500;
constexpr unsigned long WIFI_CONNECT_TIMEOUT_MS = 20000;
constexpr unsigned long HTTP_TIMEOUT_MS = 10000;

constexpr const char* DEFAULT_API_URL = "https://smartos-rw.unique-monsters.my.id/api/rfid/tap";
constexpr const char* AP_SSID = "SmartRW-RFID";
constexpr const char* AP_PASS = "smartrw2026";
constexpr const char* PREF_NAMESPACE = "smartrw-rfid";

// Root CA Let's Encrypt (ISRG Root X1, valid s/d 2035) untuk validasi TLS HTTPS
const char* ISRG_ROOT_X1 = R"CERT(
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF
+0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX
6UA5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq
+sWT8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR
3qyHB5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j
3x+UCB5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b
0SHzUvKBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH
0ahmbWnOlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xD
k3SzynTnjh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaH
WBfEbwrbwqHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ1
3hef4Y53CIrU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQE
AwIBBjAPBgNVHRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY
9umbbjANBgkqhkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu
+ILlaS/V9lZLubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+
GnY+EgPbk6ZGQ3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X
/qkSsisRcOj/KKNFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCv
jHIInNzktHCgKQ5ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNb
OhdjsnvzqvHu7UrTkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nx
e5AW0wdeRlN8NwdCjNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1
NbdWhscdCb+ZAJzVcoyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1l
vh+wjChP4kqKOJ2qxq4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0
GE44Za4rF2LN9d11TPAmRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq3
7Z0j4r7g1SgEEzwxA57demyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/
+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----
)CERT";

// ======================= OBJEK GLOBAL ==========================
Preferences prefs;
MFRC522 mfrc522(RC522_SS_PIN, RC522_RST_PIN);
LiquidCrystal_I2C* lcd = nullptr;
Servo gateServo;
WebServer webServer(80);
DNSServer dnsServer;

String wifiSsid = "";
String wifiPass = "";
String apiUrl = DEFAULT_API_URL;
String sessionToken = "";

String scannedWifiOptionsHtml = "";
String lastUid = "";
unsigned long lastTapMs = 0;
unsigned long setupBtnPressStartMs = 0;
bool setupBtnHolding = false;
bool portalActive = false;
bool ntpSynced = false;

// ======================= UTILITAS LCD & BUZZER =================
String padLine16(const String& text) {
  String out = text;
  if (out.length() > 16) {
    out = out.substring(0, 16);
  }
  while (out.length() < 16) {
    out += ' ';
  }
  return out;
}

void lcdShow(const String& line1, const String& line2 = "") {
  if (!lcd) return;
  lcd->setCursor(0, 0);
  lcd->print(padLine16(line1));
  lcd->setCursor(0, 1);
  lcd->print(padLine16(line2));
}

void showStandbyScreen() {
  lcdShow("Smart RW - RFID", "Tap Kartu Warga ");
}

void beepMs(uint16_t ms) {
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_LEVEL);
  delay(ms);
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_LEVEL == HIGH ? LOW : HIGH);
}

void beepSuccess() {
  beepMs(150);
}

void beepRegistered() {
  beepMs(80);
  delay(80);
  beepMs(80);
}

void beepAlreadyPresent() {
  beepMs(110);
  delay(70);
  beepMs(110);
}

void beepError() {
  for (int i = 0; i < 3; i++) {
    beepMs(160);
    if (i < 2) delay(90);
  }
}

void openGateServo() {
  gateServo.write(SERVO_OPEN_ANGLE);
  delay(SERVO_OPEN_MS);
  gateServo.write(SERVO_CLOSE_ANGLE);
}

uint8_t detectLcdAddress() {
  const uint8_t candidates[] = {0x27, 0x3F};
  for (uint8_t addr : candidates) {
    Wire.beginTransmission(addr);
    if (Wire.endTransmission() == 0) {
      return addr;
    }
  }
  return 0x27;
}

// ======================= VALIDASI & HELPER =====================
bool isHex64(const String& val) {
  if (val.length() != 64) return false;
  for (size_t i = 0; i < val.length(); i++) {
    char c = val.charAt(i);
    bool valid = (c >= '0' && c <= '9') || (c >= 'a' && c <= 'f') || (c >= 'A' && c <= 'F');
    if (!valid) return false;
  }
  return true;
}

bool isValidApiUrl(const String& url) {
  return url.startsWith("https://") || url.startsWith("http://");
}

String htmlEscape(const String& input) {
  String out;
  out.reserve(input.length() + 16);
  for (size_t i = 0; i < input.length(); i++) {
    char c = input.charAt(i);
    switch (c) {
      case '&': out += F("&amp;"); break;
      case '<': out += F("&lt;"); break;
      case '>': out += F("&gt;"); break;
      case '"': out += F("&quot;"); break;
      case '\'': out += F("&#39;"); break;
      default: out += c; break;
    }
  }
  return out;
}

String extractJsonString(const String& json, const String& key) {
  String pattern = "\"" + key + "\"";
  int keyIdx = json.indexOf(pattern);
  if (keyIdx < 0) return "";
  int colonIdx = json.indexOf(':', keyIdx + pattern.length());
  if (colonIdx < 0) return "";
  int startQuote = json.indexOf('"', colonIdx + 1);
  if (startQuote < 0) return "";
  int endQuote = json.indexOf('"', startQuote + 1);
  if (endQuote < 0) return "";
  return json.substring(startQuote + 1, endQuote);
}

void loadConfig() {
  prefs.begin(PREF_NAMESPACE, true);
  wifiSsid = prefs.getString("ssid", "");
  wifiPass = prefs.getString("pass", "");
  apiUrl = prefs.getString("url", DEFAULT_API_URL);
  sessionToken = prefs.getString("token", "");
  prefs.end();

  if (apiUrl.isEmpty()) {
    apiUrl = DEFAULT_API_URL;
  }
}

void saveConfig(const String& ssid, const String& pass, const String& url, const String& token) {
  prefs.begin(PREF_NAMESPACE, false);
  prefs.putString("ssid", ssid);
  prefs.putString("pass", pass);
  prefs.putString("url", url);
  prefs.putString("token", token);
  prefs.end();
}

// ======================= CAPTIVE PORTAL AP =====================
void scanWifiNetworks() {
  scannedWifiOptionsHtml = "";
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  delay(100);
  int n = WiFi.scanNetworks();
  if (n > 0) {
    for (int i = 0; i < n; i++) {
      String ssid = WiFi.SSID(i);
      if (ssid.isEmpty()) continue;
      String escaped = htmlEscape(ssid);
      scannedWifiOptionsHtml += "<option value=\"" + escaped + "\"";
      if (ssid == wifiSsid) scannedWifiOptionsHtml += " selected";
      scannedWifiOptionsHtml += ">" + escaped + " (" + String(WiFi.RSSI(i)) + " dBm)</option>";
    }
  }
  WiFi.scanDelete();
}

String buildPortalPage(const String& statusBanner = "") {
  String html = F(
    "<!DOCTYPE html><html lang=\"id\"><head>"
    "<meta charset=\"utf-8\">"
    "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
    "<title>Setup Smart RW RFID</title>"
    "<style>"
    "body{font-family:system-ui,-apple-system,sans-serif;background:#f1f5f9;color:#0f172a;margin:0;padding:16px}"
    ".card{max-width:460px;margin:0 auto;background:#fff;border-radius:16px;padding:20px;box-shadow:0 4px 20px rgba(0,0,0,.08)}"
    "h1{font-size:20px;margin:0 0 6px;color:#047857}p.sub{font-size:13px;color:#475569;margin:0 0 16px}"
    "label{display:block;font-size:13px;font-weight:600;margin:12px 0 4px}"
    "input,select{width:100%;box-sizing:border-box;padding:10px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px}"
    ".hint{font-size:12px;color:#64748b;margin-top:4px}"
    ".msg{padding:10px 12px;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:13px;margin-bottom:12px}"
    ".ok{background:#ecfdf5;color:#065f46}"
    "button{width:100%;margin-top:18px;padding:12px;border:0;border-radius:10px;background:#059669;color:#fff;font-size:15px;font-weight:700;cursor:pointer}"
    "</style></head><body><div class=\"card\">"
    "<h1>Smart RW - Setup RFID</h1>"
    "<p class=\"sub\">Atur WiFi, URL server, dan Token Sesi 64 karakter dari menu Kegiatan &rarr; Absensi RFID.</p>"
  );

  if (!statusBanner.isEmpty()) {
    html += "<div class=\"msg\">" + htmlEscape(statusBanner) + "</div>";
  }

  html += F("<form method=\"POST\" action=\"/save\">");

  if (!scannedWifiOptionsHtml.isEmpty()) {
    html += F("<label for=\"ssid_pick\">Pilih WiFi Terdeteksi</label>"
              "<select id=\"ssid_pick\" onchange=\"if(this.value)document.getElementById('ssid').value=this.value\">"
              "<option value=\"\">-- Pilih jaringan atau ketik manual --</option>");
    html += scannedWifiOptionsHtml;
    html += F("</select>");
  }

  html += "<label for=\"ssid\">Nama WiFi (SSID)</label>";
  html += "<input id=\"ssid\" name=\"ssid\" required maxlength=\"64\" value=\"" + htmlEscape(wifiSsid) + "\" placeholder=\"Contoh: WiFi-Balai-RW\">";

  html += "<label for=\"pass\">Password WiFi</label>";
  html += "<input id=\"pass\" name=\"pass\" type=\"password\" maxlength=\"64\" placeholder=\"";
  html += wifiPass.isEmpty() ? "Kosongkan jika WiFi tanpa password" : "Tersimpan (kosongkan jika tidak diubah)";
  html += "\">";
  html += "<label style=\"font-weight:400;display:flex;align-items:center;gap:8px;margin-top:6px\">"
          "<input type=\"checkbox\" name=\"open_wifi\" value=\"1\" style=\"width:auto\"> WiFi tanpa password (Open)"
          "</label>";

  html += "<label for=\"url\">URL Endpoint API</label>";
  html += "<input id=\"url\" name=\"url\" type=\"url\" required maxlength=\"200\" value=\"" + htmlEscape(apiUrl) + "\">";
  html += "<div class=\"hint\">Default: " + htmlEscape(String(DEFAULT_API_URL)) + "</div>";

  html += "<label for=\"token\">Token Sesi RFID (64 karakter hex)</label>";
  html += "<input id=\"token\" name=\"token\" type=\"password\" maxlength=\"64\" autocomplete=\"off\" placeholder=\"";
  html += isHex64(sessionToken) ? "Token tersimpan (kosongkan jika tidak ganti)" : "Tempel 64 karakter token sesi di sini";
  html += "\">";
  html += "<div class=\"hint\">Status token: ";
  html += isHex64(sessionToken) ? "<strong>Sudah tersimpan</strong>" : "<strong>Belum diisi</strong>";
  html += "</div>";

  html += F("<button type=\"submit\">Simpan &amp; Hubungkan Alat</button></form></div></body></html>");
  return html;
}

void handlePortalRoot() {
  webServer.send(200, "text/html", buildPortalPage());
}

void handlePortalSave() {
  String newSsid = webServer.arg("ssid");
  String newPass = webServer.arg("pass");
  bool openWifi = webServer.arg("open_wifi") == "1";
  String newUrl = webServer.arg("url");
  String newToken = webServer.arg("token");

  newSsid.trim();
  newUrl.trim();
  newToken.trim();
  newToken.toLowerCase();

  if (newSsid.isEmpty()) {
    webServer.send(400, "text/html", buildPortalPage("Nama WiFi (SSID) wajib diisi."));
    return;
  }

  if (!isValidApiUrl(newUrl)) {
    webServer.send(400, "text/html", buildPortalPage("URL API harus diawali https:// atau http://"));
    return;
  }

  String finalPass = openWifi ? "" : (newPass.isEmpty() ? wifiPass : newPass);
  String finalToken = newToken.isEmpty() ? sessionToken : newToken;

  if (!isHex64(finalToken)) {
    webServer.send(400, "text/html", buildPortalPage("Token Sesi RFID harus tepat 64 karakter hex (0-9, a-f)."));
    return;
  }

  saveConfig(newSsid, finalPass, newUrl, finalToken);

  webServer.send(200, "text/html",
    "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\">"
    "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
    "<title>Tersimpan</title><style>body{font-family:system-ui;padding:24px;background:#ecfdf5;color:#065f46}</style>"
    "</head><body><h2>Pengaturan Disimpan!</h2><p>ESP32 sedang restart dan menghubungkan ke WiFi...</p></body></html>");

  lcdShow("Config Tersimpan", "Restart alat... ");
  beepRegistered();
  delay(1200);
  ESP.restart();
}

void startConfigPortal(const String& reasonLine = "Mode Pengaturan ") {
  portalActive = true;
  gateServo.write(SERVO_CLOSE_ANGLE);

  lcdShow(reasonLine, "Scan WiFi...    ");
  scanWifiNetworks();

  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASS);
  delay(200);

  IPAddress apIp = WiFi.softAPIP();
  dnsServer.start(53, "*", apIp);

  webServer.on("/", HTTP_GET, handlePortalRoot);
  webServer.on("/save", HTTP_POST, handlePortalSave);
  webServer.onNotFound([]() {
    webServer.sendHeader("Location", String("http://") + WiFi.softAPIP().toString() + "/", true);
    webServer.send(302, "text/plain", "");
  });
  webServer.begin();

  Serial.println("[PORTAL] Hotspot aktif. SSID: " + String(AP_SSID) + " | IP: " + apIp.toString());

  unsigned long lastLcdFlip = 0;
  bool showIpScreen = false;

  while (true) {
    dnsServer.processNextRequest();
    webServer.handleClient();

    if (millis() - lastLcdFlip >= 3000) {
      lastLcdFlip = millis();
      showIpScreen = !showIpScreen;
      if (showIpScreen) {
        lcdShow("Buka Browser HP ", apIp.toString());
      } else {
        lcdShow("AP:" + String(AP_SSID), "PW:" + String(AP_PASS));
      }
    }
    delay(5);
  }
}

// ======================= KONEKSI WIFI & NTP ====================
bool connectWifiAndSyncTime() {
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  if (wifiPass.isEmpty()) {
    WiFi.begin(wifiSsid.c_str());
  } else {
    WiFi.begin(wifiSsid.c_str(), wifiPass.c_str());
  }

  lcdShow("Konek WiFi...", wifiSsid);
  unsigned long startMs = millis();

  while (WiFi.status() != WL_CONNECTED) {
    // Izinkan tekan BOOT 2.5 detik saat mencoba konek WiFi untuk ganti setup
    if (digitalRead(SETUP_BTN_PIN) == LOW) {
      if (!setupBtnHolding) {
        setupBtnHolding = true;
        setupBtnPressStartMs = millis();
      } else if (millis() - setupBtnPressStartMs >= HOLD_SETUP_BTN_MS) {
        startConfigPortal("Setup Manual    ");
      }
    } else {
      setupBtnHolding = false;
    }

    if (millis() - startMs >= WIFI_CONNECT_TIMEOUT_MS) {
      return false;
    }
    delay(150);
  }

  Serial.println("[WIFI] Terhubung. IP: " + WiFi.localIP().toString());

  // Sinkronisasi jam NTP (wajib agar validasi tanggal sertifikat TLS berhasil)
  if (apiUrl.startsWith("https://")) {
    lcdShow("Sinkron Jam NTP ", "Mohon tunggu... ");
    configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com");

    unsigned long ntpStart = millis();
    time_t nowSec = time(nullptr);
    while (nowSec < 1700000000 && (millis() - ntpStart < 10000)) {
      delay(250);
      nowSec = time(nullptr);
    }
    ntpSynced = (nowSec >= 1700000000);
    Serial.println(ntpSynced ? "[NTP] Sinkron WIB OK" : "[NTP] Gagal sinkron, lanjut coba...");
  }

  return true;
}

// ======================= KIRIM TAP KE SERVER ===================
void handleApiResponse(int httpCode, const String& body) {
  Serial.printf("[API] HTTP %d | %s\n", httpCode, body.c_str());

  if (httpCode == 200) {
    String status = extractJsonString(body, "status");
    if (status == "present") {
      lcdShow("Hadir Tercatat! ", "Silakan Masuk   ");
      beepSuccess();
      openGateServo();
    } else if (status == "registered") {
      lcdShow("Kartu Terdaftar ", "Tap Lagi U/Hadir");
      beepRegistered();
      delay(2000);
    } else if (status == "already_present") {
      lcdShow("Sudah Absen     ", "Tercatat Sblmnya");
      beepAlreadyPresent();
      if (OPEN_SERVO_ON_ALREADY_PRESENT) {
        openGateServo();
      } else {
        delay(2000);
      }
    } else if (status == "koperasi_registered") {
      lcdShow("Kartu Terdaftar ", "Siap Daftar Kop.");
      beepRegistered();
      delay(2000);
    } else if (status == "koperasi_verified") {
      lcdShow("Tap Koperasi OK ", "Lanjut Daftar   ");
      beepSuccess();
      delay(2000);
    } else if (status == "koperasi_already_verified") {
      lcdShow("Kartu Sdh Tap   ", "Silakan Daftar  ");
      beepAlreadyPresent();
      delay(2000);
    } else if (status == "ktp_registered") {
      lcdShow("KTP Terdaftar!  ", "Aktif di RT&Web ");
      beepRegistered();
      delay(2000);
    } else if (status == "ktp_verified") {
      lcdShow("KTP Terverifikas", "Terdaftar di RT ");
      beepSuccess();
      delay(2000);
    } else if (status == "ktp_already_verified") {
      lcdShow("KTP Sdh Terdaft.", "Terdaftar di RT ");
      beepAlreadyPresent();
      delay(2000);
    } else {
      lcdShow("Respon OK       ", status);
      beepSuccess();
      delay(1500);
    }
    return;
  }

  // Error: pastikan palang tetap tertutup dan bunyi error
  gateServo.write(SERVO_CLOSE_ANGLE);

  if (httpCode == 401) {
    lcdShow("Token Salah/Exp!", "Tahan BOOT 3dtk ");
  } else if (httpCode == 403) {
    lcdShow("Akses Ditolak   ", "Cek Izin Petugas");
  } else if (httpCode == 404) {
    lcdShow("Warga Tdk Aktif ", "Atau Luar Wil.  ");
  } else if (httpCode == 409) {
    lcdShow("Kegiatan Belum  ", "Mulai / Ditutup ");
  } else if (httpCode == 422) {
    if (body.indexOf("kedaluwarsa") >= 0) {
      lcdShow("Pairing Habis!  ", "Ulangi di Panel ");
    } else if (body.indexOf("terdaftar") >= 0 && body.indexOf("belum") < 0) {
      lcdShow("Kartu Sdh Pakai ", "Cabut Kartu Dulu");
    } else {
      lcdShow("Kartu Blm Daftar", "Daftar di Panel ");
    }
  } else if (httpCode == 429) {
    lcdShow("Terlalu Cepat!  ", "Tunggu Sebentar ");
  } else {
    lcdShow("Koneksi Gagal!  ", "Kode: " + String(httpCode));
  }

  beepError();
  delay(2000);
}

void sendTapToServer(const String& uid) {
  if (WiFi.status() != WL_CONNECTED) {
    lcdShow("WiFi Terputus!  ", "Menghubungkan...");
    beepError();
    WiFi.reconnect();
    delay(2000);
    showStandbyScreen();
    return;
  }

  lcdShow("Memproses Tap...", uid);

  if (apiUrl.startsWith("https://") && time(nullptr) < 1700000000) {
    configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com");
    unsigned long ntpRetryStart = millis();
    while (time(nullptr) < 1700000000 && (millis() - ntpRetryStart < 5000)) {
      delay(200);
    }
  }

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);
  http.setFollowRedirects(HTTPC_DISABLE_FOLLOW_REDIRECTS);

  WiFiClient plainClient;
  WiFiClientSecure secureClient;
  bool began = false;

  if (apiUrl.startsWith("https://")) {
    secureClient.setCACert(ISRG_ROOT_X1);
    began = http.begin(secureClient, apiUrl);
  } else {
    began = http.begin(plainClient, apiUrl);
  }

  if (!began) {
    lcdShow("URL Tidak Valid ", "Tahan BOOT 3dtk ");
    beepError();
    delay(2000);
    showStandbyScreen();
    return;
  }

  http.addHeader("Accept", "application/json");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", "Bearer " + sessionToken);

  String payload = "{\"uid\":\"" + uid + "\"}";
  int httpCode = http.POST(payload);
  String body = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);
  http.end();

  handleApiResponse(httpCode, body);
  showStandbyScreen();
}

// ======================= SETUP & LOOP ==========================
void setup() {
  Serial.begin(115200);
  delay(200);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_LEVEL == HIGH ? LOW : HIGH);
  pinMode(SETUP_BTN_PIN, INPUT_PULLUP);

  Wire.begin(LCD_SDA_PIN, LCD_SCL_PIN);
  uint8_t lcdAddr = detectLcdAddress();
  lcd = new LiquidCrystal_I2C(lcdAddr, 16, 2);
  lcd->init();
  lcd->backlight();
  lcdShow("Smart RW - RFID ", "Memulai alat... ");

  ESP32PWM::allocateTimer(0);
  gateServo.setPeriodHertz(50);
  gateServo.attach(SERVO_PIN, 500, 2400);
  gateServo.write(SERVO_CLOSE_ANGLE);

  SPI.begin(RC522_SCK_PIN, RC522_MISO_PIN, RC522_MOSI_PIN, RC522_SS_PIN);
  mfrc522.PCD_Init();
  delay(50);

  loadConfig();

  if (wifiSsid.isEmpty() || !isHex64(sessionToken)) {
    startConfigPortal("Belum Diatur    ");
  }

  if (!connectWifiAndSyncTime()) {
    startConfigPortal("WiFi Gagal Konek");
  }

  beepRegistered();
  showStandbyScreen();
}

void loop() {
  // Cek tahan tombol BOOT (>= 2.5 detik) untuk masuk mode Hotspot Pengaturan
  if (digitalRead(SETUP_BTN_PIN) == LOW) {
    if (!setupBtnHolding) {
      setupBtnHolding = true;
      setupBtnPressStartMs = millis();
    } else if (millis() - setupBtnPressStartMs >= HOLD_SETUP_BTN_MS) {
      startConfigPortal("Mode Pengaturan ");
    }
  } else {
    setupBtnHolding = false;
  }

  // Baca kartu RFID
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(40);
    return;
  }

  uint8_t uidSize = mfrc522.uid.size;
  if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    lcdShow("UID Tdk Valid!  ", "Coba Tap Ulang  ");
    beepError();
    delay(1500);
    showStandbyScreen();
    return;
  }

  String uid = "";
  uid.reserve(uidSize * 2);
  for (uint8_t i = 0; i < uidSize; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += '0';
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();

  unsigned long nowMs = millis();
  if (uid == lastUid && (nowMs - lastTapMs) < SAME_UID_COOLDOWN_MS) {
    return;
  }
  if ((nowMs - lastTapMs) < ANY_TAP_COOLDOWN_MS) {
    return;
  }

  lastUid = uid;
  lastTapMs = nowMs;

  sendTapToServer(uid);
}
