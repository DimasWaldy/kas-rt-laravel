/*
 * ============================================================
 * Smart RW - Terminal Universal RFID / e-KTP (Multi-Projek)
 * ============================================================
 * Fitur Universal:
 *   - 1 Firmware untuk 4 Mahasiswa / Projek Berbeda:
 *       1. Dimas  : https://smartos-rw.unique-monsters.my.id/
 *       2. Julius : https://guyub.unique-monsters.my.id/
 *       3. Ilham  : https://kawan.unique-monsters.my.id/
 *       4. Fabian : https://e-warga.unique-monsters.my.id/
 *       5. Custom / Bebas (isi URL & Token sendiri)
 *   - Web Portal Konfigurasi via HP:
 *       Hotspot AP : ESP32_1_JDIF
 *       Password   : 876543210
 *       IP Portal  : http://192.168.4.1
 *   - Pemicu Masuk ke Portal:
 *       1. Tahan tombol fisik BOOT (GPIO 0) selama 3 detik.
 *       2. Otomatis menyala jika WiFi gagal terhubung dalam 25 detik.
 *       3. Portal otomatis timeout setelah 5 menit jika tidak digunakan.
 *   - Penyimpanan Permanen: Konfigurasi disimpan di NVS Flash (Preferences)
 *   - Hardware: Kompatibel ESP32 DevKit (30/38 pin) & Nano ESP32 + RC522
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <Preferences.h>
#include <SPI.h>
#include <MFRC522.h>

// ============================================================
// 1. PIN RC522 & BUZZER & TOMBOL BOOT
// ============================================================
#if defined(ARDUINO_NANO_ESP32)
  #define BOARD_NAME "Arduino Nano ESP32"
  constexpr uint8_t PIN_SS   = D10;
  constexpr uint8_t PIN_RST  = D9;
  constexpr uint8_t PIN_SCK  = D13;
  constexpr uint8_t PIN_MOSI = D11;
  constexpr uint8_t PIN_MISO = D12;
#else
  #define BOARD_NAME "ESP32 DevKit Standar"
  constexpr uint8_t PIN_SS   = 5;
  constexpr uint8_t PIN_RST  = 22; // Pin 22 standar, otomatis fallback ke 27 jika perlu
  constexpr uint8_t PIN_SCK  = 18;
  constexpr uint8_t PIN_MOSI = 23;
  constexpr uint8_t PIN_MISO = 19;
#endif

constexpr uint8_t PIN_BUZZER = 25; // Buzzer (+) ke GPIO 25, (-) ke GND
constexpr uint8_t PIN_BOOT   = 0;  // Tombol BOOT bawaan ESP32 (GPIO 0)

// Hotspot Konfigurasi ESP32
const char* AP_SSID = "ESP32_1_JDIF";
const char* AP_PASS = "876543210";

// ============================================================
// 2. OBJEK GLOBAL & VARIABEL KONFIGURASI
// ============================================================
Preferences prefs;
DNSServer dnsServer;
WebServer webServer(80);
MFRC522 rfid(PIN_SS, PIN_RST);

// Konfigurasi Dinamis (disimpan di NVS Flash)
String cfg_proj_name;
String cfg_wifi_ssid;
String cfg_wifi_pass;
String cfg_api_url;
String cfg_hb_url;
String cfg_dev_key;
String cfg_dev_id;

bool rc522Ready = false;
bool inPortalMode = false;
String lastUID = "";
unsigned long lastScanTime = 0;
unsigned long lastHeartbeatTime = 0;
unsigned long bootPressStartTime = 0;

const unsigned long SCAN_COOLDOWN_MS = 2000;
const unsigned long HEARTBEAT_INTERVAL_MS = 2000;

// Prototype Fungsi
void loadConfig();
void saveConfig(const String& proj, const String& ssid, const String& pass, 
                const String& tapUrl, const String& hbUrl, const String& key, const String& id);
void startConfigPortal();
void handlePortalRoot();
void handlePortalSave();
void handlePortalExit();
void connectWiFi();
void testRc522Connection();
String readUID();
void sendTapToServer(const String& uid);
void sendHeartbeat();
void beepSuccess();
void beepError();
void beepPortal();

// ============================================================
// 3. SETUP UTAMA
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(1000);

    pinMode(PIN_BOOT, INPUT_PULLUP);
    pinMode(PIN_BUZZER, OUTPUT);
    digitalWrite(PIN_BUZZER, LOW);

#if !defined(ARDUINO_NANO_ESP32)
    pinMode(22, OUTPUT);
    digitalWrite(22, HIGH);
    pinMode(27, OUTPUT);
    digitalWrite(27, HIGH);
    delay(20);
#endif

    // Inisialisasi SPI & RC522
#if defined(ARDUINO_NANO_ESP32)
    SPI.begin();
#else
    SPI.begin(PIN_SCK, PIN_MISO, PIN_MOSI, PIN_SS);
#endif
    rfid.PCD_Init();
    delay(40);
    testRc522Connection();

    // Muat konfigurasi dari memori flash NVS
    loadConfig();

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.println(F("   ESP32 UNIVERSAL RFID READER (4 IN 1 PROJECT)   "));
    Serial.printf ("   Board          : %s\n", BOARD_NAME);
    Serial.printf ("   Projek Aktif   : %s\n", cfg_proj_name.c_str());
    Serial.printf ("   Target Tap URL : %s\n", cfg_api_url.c_str());
    Serial.printf ("   Device Key     : %s\n", cfg_dev_key.c_str());
    Serial.println(F("--------------------------------------------------"));
    Serial.println(F("💡 TIPS GANTI PROJEK / WIFI:"));
    Serial.println(F("   Tahan tombol BOOT di ESP32 selama 3 detik, atau"));
    Serial.println(F("   sambungkan ke Hotspot: ESP32_1_JDIF (192.168.4.1)"));
    Serial.println(F("=================================================="));

    // Cek apakah tombol BOOT sedang ditahan saat dinyalakan
    if (digitalRead(PIN_BOOT) == LOW) {
        Serial.println(F("\n[PORTAL] Tombol BOOT terdeteksi ditekan saat start! Masuk Portal..."));
        beepPortal();
        startConfigPortal();
        return;
    }

    // Jika SSID kosong, langsung masuk ke Portal
    if (cfg_wifi_ssid.length() == 0) {
        Serial.println(F("\n[PORTAL] Belum ada konfigurasi WiFi tersimpan. Membuka Portal..."));
        beepPortal();
        startConfigPortal();
        return;
    }

    // Coba koneksi ke WiFi yang tersimpan
    connectWiFi();
}

// ============================================================
// 4. LOOP UTAMA
// ============================================================
void loop() {
    // 1. Deteksi Tombol Fisik BOOT (GPIO 0) ditahan 3 detik
    if (digitalRead(PIN_BOOT) == LOW) {
        if (bootPressStartTime == 0) {
            bootPressStartTime = millis();
        } else if (millis() - bootPressStartTime > 3000) {
            Serial.println(F("\n[PORTAL] Tombol BOOT ditekan 3 detik! Buka Portal Konfigurasi..."));
            beepPortal();
            startConfigPortal();
            bootPressStartTime = 0;
            return;
        }
    } else {
        bootPressStartTime = 0;
    }

    // 2. Jika sedang di mode Portal Konfigurasi, proses request HTTP & DNS
    if (inPortalMode) {
        dnsServer.processNextRequest();
        webServer.handleClient();
        delay(5);
        return;
    }

    // 3. Jaga koneksi WiFi (Auto-reconnect aman tanpa spam)
    if (WiFi.status() != WL_CONNECTED) {
        static unsigned long lastReconnect = 0;
        if (millis() - lastReconnect > 15000) {
            lastReconnect = millis();
            Serial.println(F("[WIFI] Terputus. Sedang mencoba menyambung ulang..."));
            WiFi.disconnect();
            delay(100);
            WiFi.begin(cfg_wifi_ssid.c_str(), cfg_wifi_pass.c_str());
        }
    }

    // 4. Heartbeat berkala ke web server (tanda alat ONLINE)
    if (WiFi.status() == WL_CONNECTED && millis() - lastHeartbeatTime >= HEARTBEAT_INTERVAL_MS) {
        lastHeartbeatTime = millis();
        sendHeartbeat();
    }

    // 5. Cek sensor RC522 jika belum terhubung
    if (!rc522Ready) {
        static unsigned long lastRetry = 0;
        if (millis() - lastRetry > 3000) {
            lastRetry = millis();
            testRc522Connection();
        }
        return;
    }

    // 6. Cek kehadiran kartu RFID / e-KTP
    bool cardPresent = rfid.PICC_IsNewCardPresent();
    if (!cardPresent) {
        byte bufferATQA[2];
        byte bufferSize = sizeof(bufferATQA);
        rfid.PCD_WriteRegister(rfid.TxModeReg, 0x00);
        rfid.PCD_WriteRegister(rfid.RxModeReg, 0x00);
        rfid.PCD_WriteRegister(rfid.ModWidthReg, 0x26);
        auto status = rfid.PICC_WakeupA(bufferATQA, &bufferSize);
        if (status == MFRC522::STATUS_OK || status == MFRC522::STATUS_COLLISION) {
            cardPresent = true;
        }
    }

    if (!cardPresent) {
        delay(10);
        return;
    }

    if (!rfid.PICC_ReadCardSerial()) {
        delay(15);
        return;
    }

    String uid = readUID();
    uint8_t uidSize = rfid.uid.size;
    if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        delay(50);
        return;
    }

    // Anti-spam jika kartu yang sama ditempel terus
    if (uid == lastUID && (millis() - lastScanTime) < SCAN_COOLDOWN_MS) {
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        return;
    }

    lastUID = uid;
    lastScanTime = millis();

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.printf ("💳 [SCAN] KARTU TERDETEKSI! UID: %s (%d byte)\n", uid.c_str(), uidSize);
    Serial.printf ("🎯 [TARGET] Mengirim ke Projek: %s\n", cfg_proj_name.c_str());
    Serial.println(F("=================================================="));

    sendTapToServer(uid);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}

// ============================================================
// 5. MANAJEMEN KONFIGURASI NVS (PREFERENCES)
// ============================================================
void loadConfig() {
    prefs.begin("rfid_cfg", true); // Mode read-only

    cfg_proj_name = prefs.getString("proj", "Dimas (Smart RW)");
    cfg_wifi_ssid = prefs.getString("ssid", "Poco F6505");
    cfg_wifi_pass = prefs.getString("pass", "dimas12345");
    cfg_api_url   = prefs.getString("tap",  "https://smartos-rw.unique-monsters.my.id/api/rfid/tap");
    cfg_hb_url    = prefs.getString("hb",   "https://smartos-rw.unique-monsters.my.id/api/rfid/heartbeat");
    cfg_dev_key   = prefs.getString("key",  "smartrw-rfid-device");
    cfg_dev_id    = prefs.getString("id",   "esp32-scanner-01");

    prefs.end();
}

void saveConfig(const String& proj, const String& ssid, const String& pass, 
                const String& tapUrl, const String& hbUrl, const String& key, const String& id) {
    prefs.begin("rfid_cfg", false); // Mode read-write

    prefs.putString("proj", proj);
    prefs.putString("ssid", ssid);
    prefs.putString("pass", pass);
    prefs.putString("tap",  tapUrl);
    prefs.putString("hb",   hbUrl);
    prefs.putString("key",  key);
    prefs.putString("id",   id);

    prefs.end();
    Serial.println(F("[NVS] Konfigurasi berhasil disimpan permanen ke Flash ESP32!"));
}

// ============================================================
// 6. KONEKSI KE WIFI
// ============================================================
void connectWiFi() {
    WiFi.persistent(false);
    WiFi.disconnect(true);
    delay(100);
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);

    Serial.printf("[WIFI] Menghubungkan ke: %s ...\n", cfg_wifi_ssid.c_str());
    WiFi.begin(cfg_wifi_ssid.c_str(), cfg_wifi_pass.c_str());

    unsigned long startAttempt = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - startAttempt < 25000) {
        delay(500);
        Serial.print(F("."));
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println(F("\n[WIFI] Berhasil terhubung!"));
        Serial.printf("[WIFI] IP ESP32 : %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("[WIFI] RSSI     : %d dBm\n", WiFi.RSSI());

        // Konfigurasi DNS Google & Cloudflare untuk stabilitas
        IPAddress dnsGoogle(8, 8, 8, 8);
        IPAddress dnsCloudflare(1, 1, 1, 1);
        WiFi.config(INADDR_NONE, INADDR_NONE, INADDR_NONE, dnsGoogle, dnsCloudflare);

        // Uji DNS resolve
        IPAddress srvIP;
        if (WiFi.hostByName("smartos-rw.unique-monsters.my.id", srvIP)) {
            Serial.printf("[DNS SUKSES] VPS IP terdeteksi: %s\n", srvIP.toString().c_str());
        }

        if (rc522Ready) beepSuccess();
        sendHeartbeat();
    } else {
        Serial.println(F("\n⚠️ [WIFI GAGAL] Tidak dapat terhubung dalam 25 detik!"));
        Serial.println(F("👉 Otomatis membuka Portal Konfigurasi Hotspot..."));
        beepPortal();
        startConfigPortal();
    }
}

// ============================================================
// 7. WEB PORTAL KONFIGURASI (CAPTIVE PORTAL VIA HP)
// ============================================================
void startConfigPortal() {
    inPortalMode = true;
    WiFi.disconnect();
    WiFi.mode(WIFI_AP);

    IPAddress apIP(192, 168, 4, 1);
    IPAddress netMsk(255, 255, 255, 0);
    WiFi.softAPConfig(apIP, apIP, netMsk);
    WiFi.softAP(AP_SSID, AP_PASS);

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.println(F("🚀 [PORTAL KONFIGURASI AKTIF]"));
    Serial.printf ("   Nama Hotspot ESP32 : %s\n", AP_SSID);
    Serial.printf ("   Password Hotspot   : %s\n", AP_PASS);
    Serial.println(F("   Alamat Web Portal  : http://192.168.4.1"));
    Serial.println(F("=================================================="));

    // Redirect semua query DNS ke 192.168.4.1 (Captive Portal)
    dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
    dnsServer.start(53, "*", apIP);

    webServer.on("/", HTTP_GET, handlePortalRoot);
    webServer.on("/save", HTTP_POST, handlePortalSave);
    webServer.on("/exit", HTTP_GET, handlePortalExit);

    // Endpoint khusus deteksi captive portal di berbagai smartphone
    webServer.on("/generate_204", HTTP_GET, handlePortalRoot);
    webServer.on("/fwlink", HTTP_GET, handlePortalRoot);
    webServer.on("/connecttest.txt", HTTP_GET, handlePortalRoot);

    webServer.onNotFound([]() {
        webServer.sendHeader("Location", "http://192.168.4.1/", true);
        webServer.send(302, "text/plain", "");
    });

    webServer.begin();

    unsigned long portalStart = millis();
    const unsigned long PORTAL_TIMEOUT_MS = 300000; // 5 Menit timeout

    while (inPortalMode) {
        dnsServer.processNextRequest();
        webServer.handleClient();
        delay(5);

        // Timeout 5 menit jika tidak ada aktivitas
        if (millis() - portalStart > PORTAL_TIMEOUT_MS) {
            Serial.println(F("[PORTAL] Timeout 5 menit tercapai. Restarting ESP32..."));
            ESP.restart();
        }
    }
}

// Tampilan HTML Portal yang Cantik & Responsive di HP
void handlePortalRoot() {
    String html = F("<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>"
        "<meta name='viewport' content='width=device-width,initial-scale=1.0'>"
        "<title>ESP32 Universal Reader</title>"
        "<style>"
        "body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0fdf4;color:#1e293b;padding:15px;margin:0;}"
        ".card{background:#fff;border-radius:14px;padding:20px;max-width:440px;margin:auto;box-shadow:0 8px 24px rgba(0,0,0,0.08);}"
        "h2{color:#166534;margin-top:0;font-size:1.3rem;text-align:center;}"
        ".badge{background:#dcfce7;color:#15803d;padding:6px 12px;border-radius:20px;font-size:0.82rem;font-weight:600;display:inline-block;margin-bottom:15px;width:100%;box-sizing:border-box;text-align:center;}"
        ".btn-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:18px;}"
        ".p-btn{background:#f1f5f9;border:1px solid #cbd5e1;padding:10px 8px;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;text-align:center;transition:all .2s;}"
        ".p-btn:hover,.p-btn.active{background:#22c55e;color:#fff;border-color:#16a34a;}"
        "label{font-size:0.82rem;font-weight:600;color:#475569;display:block;margin-top:10px;margin-bottom:4px;}"
        "input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.9rem;box-sizing:border-box;}"
        "input:focus{outline:none;border-color:#22c55e;}"
        ".btn-submit{background:#16a34a;color:#fff;border:none;width:100%;padding:12px;border-radius:8px;font-size:1rem;font-weight:700;margin-top:20px;cursor:pointer;}"
        ".btn-exit{background:#64748b;color:#fff;border:none;width:100%;padding:10px;border-radius:8px;font-size:0.9rem;font-weight:600;margin-top:8px;cursor:pointer;}"
        ".footer{font-size:0.75rem;color:#94a3b8;text-align:center;margin-top:15px;}"
        "</style></head><body><div class='card'>"
        "<h2>⚡ Universal RFID Reader</h2>"
        "<div class='badge'>Aktif Sekarang: <b>");
    html += cfg_proj_name;
    html += F("</b></div>"
        "<label>PILIH SHORTCUT PROJEK:</label>"
        "<div class='btn-grid'>"
        "<button type='button' class='p-btn' onclick='pick(\"dimas\")'>🟢 Dimas (Smart RW)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"julius\")'>🔵 Julius (Guyub)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"ilham\")'>🟠 Ilham (Kawan)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"fabian\")'>🟣 Fabian (E-Warga)</button>"
        "</div>"
        "<form action='/save' method='POST'>"
        "<label>Nama Projek / Pemilik:</label>"
        "<input type='text' id='proj' name='proj' value='");
    html += cfg_proj_name;
    html += F("' required>"
        "<label>Nama WiFi / Hotspot HP (SSID):</label>"
        "<input type='text' id='ssid' name='ssid' value='");
    html += cfg_wifi_ssid;
    html += F("' placeholder='Nama Hotspot HP Anda' required>"
        "<label>Password WiFi / Hotspot:</label>"
        "<input type='text' id='pass' name='pass' value='");
    html += cfg_wifi_pass;
    html += F("' placeholder='Password Hotspot'>"
        "<label>Target URL Endpoint Tap:</label>"
        "<input type='text' id='tap' name='tap' value='");
    html += cfg_api_url;
    html += F("' required>"
        "<label>Target URL Heartbeat (Status Online):</label>"
        "<input type='text' id='hb' name='hb' value='");
    html += cfg_hb_url;
    html += F("' required>"
        "<label>Device Secret Key:</label>"
        "<input type='text' id='key' name='key' value='");
    html += cfg_dev_key;
    html += F("' required>"
        "<label>Device ID:</label>"
        "<input type='text' id='id' name='id' value='");
    html += cfg_dev_id;
    html += F("' required>"
        "<button type='submit' class='btn-submit'>💾 SIMPAN & REBOOT ALAT</button>"
        "</form>"
        "<form action='/exit' method='GET'>"
        "<button type='submit' class='btn-exit'>❌ Batal & Keluar</button>"
        "</form>"
        "<div class='footer'>ESP32 JDIF Multi-Project Universal Reader &bull; Port 192.168.4.1</div>"
        "</div>"
        "<script>"
        "const p={"
        "dimas:{n:'Dimas (Smart RW)',t:'https://smartos-rw.unique-monsters.my.id/api/rfid/tap',h:'https://smartos-rw.unique-monsters.my.id/api/rfid/heartbeat',k:'smartrw-rfid-device',i:'esp32-scanner-01'},"
        "julius:{n:'Julius (Guyub)',t:'https://guyub.unique-monsters.my.id/api/rfid/tap',h:'https://guyub.unique-monsters.my.id/api/rfid/heartbeat',k:'smartrw-rfid-device',i:'esp32-scanner-01'},"
        "ilham:{n:'Ilham (Kawan)',t:'https://kawan.unique-monsters.my.id/api/rfid/tap',h:'https://kawan.unique-monsters.my.id/api/rfid/heartbeat',k:'smartrw-rfid-device',i:'esp32-scanner-01'},"
        "fabian:{n:'Fabian (E-Warga)',t:'https://e-warga.unique-monsters.my.id/api/rfid/tap',h:'https://e-warga.unique-monsters.my.id/api/rfid/heartbeat',k:'smartrw-rfid-device',i:'esp32-scanner-01'}"
        "};"
        "function pick(k){"
        "if(!p[k])return;"
        "document.getElementById('proj').value=p[k].n;"
        "document.getElementById('tap').value=p[k].t;"
        "document.getElementById('hb').value=p[k].h;"
        "document.getElementById('key').value=p[k].k;"
        "document.getElementById('id').value=p[k].i;"
        "}"
        "</script></body></html>");

    webServer.send(200, "text/html", html);
}

void handlePortalSave() {
    String proj = webServer.arg("proj");
    String ssid = webServer.arg("ssid");
    String pass = webServer.arg("pass");
    String tap  = webServer.arg("tap");
    String hb   = webServer.arg("hb");
    String key  = webServer.arg("key");
    String id   = webServer.arg("id");

    saveConfig(proj, ssid, pass, tap, hb, key, id);

    String resp = F("<!DOCTYPE html><html><head><meta charset='UTF-8'>"
        "<meta name='viewport' content='width=device-width,initial-scale=1.0'>"
        "<title>Tersimpan</title>"
        "<style>body{font-family:sans-serif;text-align:center;padding:40px 20px;background:#f0fdf4;color:#166534;}</style>"
        "</head><body>"
        "<h1>🎉 Konfigurasi Tersimpan!</h1>"
        "<p>Alat sedang me-restart ke Projek: <b>");
    resp += proj;
    resp += F("</b></p>"
        "<p>Silakan putuskan sambungan dari WiFi ESP32 dan buka website projek Anda.</p>"
        "<script>setTimeout(function(){window.close();}, 4000);</script>"
        "</body></html>");

    webServer.send(200, "text/html", resp);
    delay(1500);

    Serial.println(F("[PORTAL] Konfigurasi baru diterima. Restarting ESP32 sekarang..."));
    ESP.restart();
}

void handlePortalExit() {
    String resp = F("<!DOCTYPE html><html><head><meta charset='UTF-8'>"
        "<meta name='viewport' content='width=device-width,initial-scale=1.0'>"
        "<title>Keluar</title>"
        "<style>body{font-family:sans-serif;text-align:center;padding:40px 20px;background:#f8fafc;color:#334155;}</style>"
        "</head><body>"
        "<h2>Keluar dari Portal</h2>"
        "<p>ESP32 kembali mencoba menyambung ke konfigurasi sebelumnya...</p>"
        "</body></html>");

    webServer.send(200, "text/html", resp);
    delay(1000);
    inPortalMode = false;
    ESP.restart();
}

// ============================================================
// 8. DIAGNOSIS HARDWARE RC522
// ============================================================
void testRc522Connection() {
    byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);

#if !defined(ARDUINO_NANO_ESP32)
    if (v == 0x00 || v == 0xFF) {
        rfid = MFRC522(PIN_SS, 27);
        rfid.PCD_Init();
        delay(40);
        v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    }
#endif

    if (v == 0x00 || v == 0xFF) {
        rc522Ready = false;
        Serial.println(F("\n⚠️  [RC522 ERROR] Modul RC522 tidak terdeteksi (0x00 / 0xFF)!"));
        Serial.println(F("   Periksa kabel jumper ke ESP32 (VCC wajib ke 3.3V, bukan 5V)."));
        beepError();
    } else {
        rc522Ready = true;
        rfid.PCD_SetAntennaGain(rfid.RxGain_43dB);
        Serial.printf("✅ Modul RC522 BERHASIL TERHUBUNG DENGAN BAIK! (Chip 0x%02X)\n", v);
    }
}

// ============================================================
// 9. BACA NOMOR UID KARTU
// ============================================================
String readUID() {
    String uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uid += "0";
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    return uid;
}

// ============================================================
// 10. KIRIM HASIL SCAN KE SERVER AKTIF
// ============================================================
void sendTapToServer(const String& uid) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println(F("⚠️  [WIFI OFFLINE] Kartu terbaca, tapi belum terhubung ke WiFi/Hotspot."));
        beepError();
        return;
    }

    WiFiClientSecure client;
    client.setInsecure();
    client.setTimeout(10);

    HTTPClient http;
    http.begin(client, cfg_api_url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + cfg_dev_key);
    http.addHeader("X-Device-Key", cfg_dev_key);
    http.setTimeout(10000);

    String payload = "{\"uid\":\"" + uid + "\",\"device_id\":\"" + cfg_dev_id + "\"}";

    Serial.printf("[HTTP] Mengirim UID %s ke %s ...\n", uid.c_str(), cfg_api_url.c_str());
    int httpCode = http.POST(payload);
    String response = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);

    Serial.printf("[HTTP] Kode Response: %d\n", httpCode);

    if (httpCode == 200) {
        Serial.printf("[HTTP] Respons Server: %s\n", response.c_str());
        Serial.println(F("🎉 [SUKSES] Data kartu berhasil diterima server!\n"));
        beepSuccess();
    } else if (httpCode == 422) {
        Serial.printf("[HTTP 422] Validasi Server: %s\n", response.c_str());
        beepError();
    } else {
        Serial.printf("[HTTP ERROR] Kode: %d | Body: %s\n", httpCode, response.c_str());
        beepError();
    }

    http.end();
}

// ============================================================
// 11. HEARTBEAT KE SERVER (STATUS ONLINE REALTIME)
// ============================================================
void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED || cfg_hb_url.length() == 0) return;

    WiFiClientSecure client;
    client.setInsecure();
    client.setTimeout(6);

    HTTPClient http;
    http.begin(client, cfg_hb_url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Key", cfg_dev_key);
    http.setTimeout(6000);

    String payload = "{\"device_id\":\"" + cfg_dev_id + "\",\"ip\":\"" + WiFi.localIP().toString() + "\"}";

    int code = http.POST(payload);
    if (code == 200) {
        static unsigned long hbCount = 0;
        if (++hbCount % 5 == 0) {
            Serial.print(F("."));
        }
    } else {
        Serial.printf("\n[HEARTBEAT] Gagal (Kode: %d | Info: %s)\n", code, http.errorToString(code).c_str());
    }
    http.end();
}

// ============================================================
// 12. INDIKATOR NADA BUZZER
// ============================================================
void beepSuccess() {
    digitalWrite(PIN_BUZZER, HIGH);
    delay(100);
    digitalWrite(PIN_BUZZER, LOW);
    delay(80);
    digitalWrite(PIN_BUZZER, HIGH);
    delay(100);
    digitalWrite(PIN_BUZZER, LOW);
}

void beepError() {
    for (int i = 0; i < 3; i++) {
        digitalWrite(PIN_BUZZER, HIGH);
        delay(150);
        digitalWrite(PIN_BUZZER, LOW);
        if (i < 2) delay(100);
    }
}

void beepPortal() {
    digitalWrite(PIN_BUZZER, HIGH);
    delay(400);
    digitalWrite(PIN_BUZZER, LOW);
    delay(100);
    digitalWrite(PIN_BUZZER, HIGH);
    delay(400);
    digitalWrite(PIN_BUZZER, LOW);
}