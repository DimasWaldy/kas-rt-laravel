/*
 * Smart RW - Absensi Kegiatan RFID + Palang Pintu Servo (ESP32 30-Pin)
 * Versi Non-Blocking (Bebas Freeze) + Auto Device Key
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
#if defined(ARDUINO_NANO_ESP32)
  constexpr uint8_t RC522_SS_PIN = D10;
  constexpr uint8_t RC522_RST_PIN = D9;
  constexpr uint8_t RC522_SCK_PIN = D13;
  constexpr uint8_t RC522_MISO_PIN = D12;
  constexpr uint8_t RC522_MOSI_PIN = D11;

  constexpr uint8_t LCD_SDA_PIN = A4;
  constexpr uint8_t LCD_SCL_PIN = A5;

  constexpr uint8_t SERVO_PIN = D6;
  constexpr uint8_t BUZZER_PIN = D7;
  constexpr uint8_t SETUP_BTN_PIN = D2;
#else
  constexpr uint8_t RC522_SS_PIN = 5;
  constexpr uint8_t RC522_RST_PIN = 27; // Pin 27 (Auto-fallback ke 22 jika diperlukan)
  constexpr uint8_t RC522_SCK_PIN = 18;
  constexpr uint8_t RC522_MISO_PIN = 19;
  constexpr uint8_t RC522_MOSI_PIN = 23;

  constexpr uint8_t LCD_SDA_PIN = 21;
  constexpr uint8_t LCD_SCL_PIN = 22;

  constexpr uint8_t SERVO_PIN = 13;
  constexpr uint8_t BUZZER_PIN = 25;
  constexpr uint8_t SETUP_BTN_PIN = 0; // Tombol BOOT
#endif

// =================== KONFIGURASI PERILAKU ALAT =================
constexpr uint8_t BUZZER_ACTIVE_LEVEL = HIGH;
constexpr int SERVO_CLOSE_ANGLE = 0;
constexpr int SERVO_OPEN_ANGLE = 90;
constexpr unsigned long SERVO_OPEN_MS = 3000;
constexpr bool OPEN_SERVO_ON_ALREADY_PRESENT = false;

constexpr unsigned long ANY_TAP_COOLDOWN_MS = 1000;
constexpr unsigned long SAME_UID_COOLDOWN_MS = 2500;
constexpr unsigned long HOLD_SETUP_BTN_MS = 2500;
constexpr unsigned long WIFI_CONNECT_TIMEOUT_MS = 15000;
constexpr unsigned long HTTP_TIMEOUT_MS = 10000;

constexpr const char* DEFAULT_API_URL = "https://smartos-rw.unique-monsters.my.id/api/rfid/tap";
constexpr const char* AP_SSID = "SmartRW-RFID";
constexpr const char* AP_PASS = "smartrw2026";
constexpr const char* PREF_NAMESPACE = "smartrw-rfid";

// Root CA Let's Encrypt untuk HTTPS
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

// 👉 ISI NAMA HOTSPOT HP & PASSWORD DI BAWAH INI:
String wifiSsid = "Poco F6505";   // Masukkan Nama Hotspot HP Anda
String wifiPass = "dimas12345";   // Masukkan Password Hotspot HP Anda
String apiUrl = DEFAULT_API_URL;
String sessionToken = "";

String scannedWifiOptionsHtml = "";
String lastUid = "";
unsigned long lastTapMs = 0;
unsigned long setupBtnPressStartMs = 0;
bool setupBtnHolding = false;
bool portalActive = false;
bool ntpSynced = false;

// ======================= UTILITAS ==============================
String padLine16(const String& text) {
  String out = text;
  if (out.length() > 16) out = out.substring(0, 16);
  while (out.length() < 16) out += ' ';
  return out;
}

void lcdShow(const String& line1, const String& line2 = "") {
  Serial.print(F("[LCD] "));
  Serial.print(line1);
  if (!line2.isEmpty()) {
    Serial.print(F(" | "));
    Serial.print(line2);
  }
  Serial.println();

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

void beepSuccess() { beepMs(150); }
void beepRegistered() { beepMs(80); delay(80); beepMs(80); }
void beepAlreadyPresent() { beepMs(110); delay(70); beepMs(110); }
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
    if (Wire.endTransmission() == 0) return addr;
  }
  return 0;
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
  String savedSsid = prefs.getString("ssid", "");
  String savedPass = prefs.getString("pass", "");
  if (!savedSsid.isEmpty()) {
    wifiSsid = savedSsid;
    wifiPass = savedPass;
  }
  apiUrl = prefs.getString("url", DEFAULT_API_URL);
  sessionToken = prefs.getString("token", "");
  prefs.end();

  if (apiUrl.isEmpty()) apiUrl = DEFAULT_API_URL;
}

// ======================= KONEKSI WIFI & NTP ====================
bool connectWifiAndSyncTime() {
  if (wifiSsid.isEmpty()) return false;

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  if (wifiPass.isEmpty()) {
    WiFi.begin(wifiSsid.c_str());
  } else {
    WiFi.begin(wifiSsid.c_str(), wifiPass.c_str());
  }

  Serial.println("[WIFI] Menghubungkan ke " + wifiSsid + "...");
  lcdShow("Konek WiFi...", wifiSsid);
  unsigned long startMs = millis();

  while (WiFi.status() != WL_CONNECTED) {
    delay(250);
    Serial.print(".");
    if (millis() - startMs >= WIFI_CONNECT_TIMEOUT_MS) {
      Serial.println("\n[WIFI] Gagal terhubung dalam 15 detik.");
      return false;
    }
  }

  Serial.println("\n[WIFI] Terhubung! IP: " + WiFi.localIP().toString());

  if (apiUrl.startsWith("https://")) {
    lcdShow("Sinkron Jam NTP ", "Mohon tunggu... ");
    configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com");
    unsigned long ntpStart = millis();
    time_t nowSec = time(nullptr);
    while (nowSec < 1700000000 && (millis() - ntpStart < 5000)) {
      delay(200);
      nowSec = time(nullptr);
    }
    ntpSynced = (nowSec >= 1700000000);
    Serial.println(ntpSynced ? "[NTP] Sinkron WIB OK" : "[NTP] Lanjut tanpa NTP...");
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
    } else if (status == "ktp_registered") {
      lcdShow("KTP Terdaftar!  ", "Aktif di RT&Web ");
      beepRegistered();
      delay(1000);
    } else if (status == "ktp_verified" || status == "ktp_already_verified") {
      lcdShow("KTP Terverifikas", "Terdaftar di RT ");
      beepSuccess();
      delay(1000);
    } else {
      lcdShow("Respon OK       ", status);
      beepSuccess();
      delay(1000);
    }
    return;
  }

  if (httpCode == 422) {
    if (body.indexOf("warga lain") >= 0 || body.indexOf("sudah terdaftar") >= 0) {
      Serial.println(F("⚠️  [PERINGATAN] Kartu e-KTP ini SUDAH TERDAFTAR di akun warga lain!"));
      lcdShow("Kartu Terdaftar ", "Di Warga Lain!  ");
    } else if (body.indexOf("kedaluwarsa") >= 0) {
      Serial.println(F("⚠️  [PERINGATAN] Sesi pendaftaran telah kedaluwarsa. Mulai ulang di web!"));
      lcdShow("Sesi Kedaluwarsa", "Ulangi di Web   ");
    } else {
      lcdShow("Tap Terbaca     ", "Periksa Website ");
    }
  } else {
    lcdShow("Koneksi Gagal!  ", "Kode: " + String(httpCode));
  }
  beepError();
  delay(1000);
}

void sendTapToServer(const String& uid) {
  Serial.println();
  Serial.println(F("--------------------------------------------------"));
  Serial.print(F("💳 [KARTU TERDETEKSI] UID: "));
  Serial.println(uid);

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println(F("⚠️  [WIFI BELUM TERKONEKSI] Kartu berhasil dibaca oleh modul RC522!"));
    Serial.println(F("   Namun data belum bisa dikirim ke website karena ESP32 belum terhubung ke WiFi/hotspot."));
    lcdShow("WiFi Terputus!  ", "UID:" + uid.substring(0, 8));
    beepError();
    delay(1500);
    showStandbyScreen();
    return;
  }

  lcdShow("Memproses Tap...", uid);

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);

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
    lcdShow("URL Error       ", "Cek Server API  ");
    beepError();
    delay(1500);
    showStandbyScreen();
    return;
  }

  http.addHeader("Accept", "application/json");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", "Bearer smartrw-rfid-device");

  String payload = "{\"uid\":\"" + uid + "\"}";
  int httpCode = http.POST(payload);
  String body = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);
  http.end();

  handleApiResponse(httpCode, body);
  showStandbyScreen();
}

// ======================= SETUP UTAMA ===========================
void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println();
  Serial.println(F("=================================================="));
  Serial.println(F("   SMART RW - RFID GATE & ATTENDANCE SYSTEM       "));
  Serial.println(F("=================================================="));

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_LEVEL == HIGH ? LOW : HIGH);
  pinMode(SETUP_BTN_PIN, INPUT_PULLUP);

  Wire.begin(LCD_SDA_PIN, LCD_SCL_PIN);
  uint8_t lcdAddr = detectLcdAddress();
  if (lcdAddr != 0) {
    lcd = new LiquidCrystal_I2C(lcdAddr, 16, 2);
    lcd->init();
    lcd->backlight();
    Serial.printf("[I2C] LCD 16x2 terdeteksi di alamat 0x%02X\n", lcdAddr);
  } else {
    Serial.println(F("[I2C] Tidak ada LCD 16x2 (Berjalan dalam mode Serial Monitor)"));
  }
  lcdShow("Smart RW - RFID ", "Memulai alat... ");

  ESP32PWM::allocateTimer(0);
  gateServo.setPeriodHertz(50);
  gateServo.attach(SERVO_PIN, 500, 2400);
  gateServo.write(SERVO_CLOSE_ANGLE);

  // Inisialisasi SPI & Modul RC522
  Serial.print(F("[RFID] Inisialisasi modul RC522... "));
#if defined(ARDUINO_NANO_ESP32)
  SPI.begin();
#else
  SPI.begin(RC522_SCK_PIN, RC522_MISO_PIN, RC522_MOSI_PIN, RC522_SS_PIN);
#endif
  mfrc522.PCD_Init();
  delay(50);

  byte v = mfrc522.PCD_ReadRegister(MFRC522::VersionReg);
#if !defined(ARDUINO_NANO_ESP32)
  if (v == 0x00 || v == 0xFF) {
    Serial.println();
    Serial.print(F("[RFID] RST di pin 27 belum merespon, mencoba pin 22... "));
    mfrc522 = MFRC522(RC522_SS_PIN, 22);
    mfrc522.PCD_Init();
    delay(50);
    v = mfrc522.PCD_ReadRegister(MFRC522::VersionReg);
  }
#endif

  if (v == 0x00 || v == 0xFF) {
    Serial.println(F("GAGAL!"));
    Serial.println(F("⚠️  [PERINGATAN] Modul RC522 TIDAK TERBACA (0x00/0xFF)!"));
    Serial.println(F("   Cek apakah kaki 8 pin RC522 sudah disolder dan kabel sudah kencang."));
  } else {
    mfrc522.PCD_SetAntennaGain(MFRC522::RxGain_max);
    Serial.printf("OK! Chip Version: 0x%02X (Antena Gain Maksimal)\n", v);
  }

  loadConfig();

  // Koneksi WiFi (Non-Blocking: jika gagal, alat tetap aktif membaca kartu)
  if (!wifiSsid.isEmpty()) {
    connectWifiAndSyncTime();
  } else {
    Serial.println(F("[WIFI] SSID belum diisi. Alat aktif dalam mode Offline."));
  }

  beepRegistered();
  showStandbyScreen();
  Serial.println(F("=================================================="));
  Serial.println(F("Status: SIAP! Tempelkan kartu e-KTP ke sensor...  "));
  Serial.println(F("=================================================="));
}

// ======================= LOOP UTAMA ============================
void loop() {
  // Cek apakah ada kartu di sensor
  if (!mfrc522.PICC_IsNewCardPresent()) {
    delay(20);
    return;
  }
  if (!mfrc522.PICC_ReadCardSerial()) {
    delay(20);
    return;
  }

  uint8_t uidSize = mfrc522.uid.size;
  if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    delay(50);
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