/*
 * ============================================================
 * Smart RW - Terminal Universal RFID / e-KTP (4-in-1 Kelompok 1)
 * ============================================================
 * Kompatibel untuk 4 Projek Mahasiswa (tinggal pilih di Web Portal):
 *   1. Dimas  : https://smartos-rw.unique-monsters.my.id/
 *   2. Julius : https://guyub.unique-monsters.my.id/
 *   3. Ilham  : https://kawan.unique-monsters.my.id/
 *   4. Fabian : https://e-warga.unique-monsters.my.id/
 *   5. Custom / Bebas
 *
 * Fitur Hardware Lengkap (Auto-Detect LCD & Safe Pin):
 *   - RC522 RFID Reader (SPI)     : SS=D5, SCK=D18, MOSI=D23, MISO=D19, RST=D27 (fallback D22 jika tanpa LCD)
 *   - Buzzer Aktif                : (+) ke D25, (-) ke GND
 *   - 1x LED Indikator            : (+) ke D26 (lewat resistor 220-330 ohm jika LED polos), (-) ke GND
 *                                   (ikut dicerminkan ke LED biru built-in board GPIO 2)
 *   - Servo SG90 (Palang)         : Signal ke D13, VCC ke 5V HW-131, GND ke GND
 *   - LCD 16x2 I2C (PCF8574T)     : SDA=D21, SCL=D22, VCC=5V HW-131, GND=GND
 *                                   (Jika LCD belum dipasang, program tetap jalan normal)
 *
 * Web Portal Konfigurasi via HP:
 *   - Hotspot AP : ESP32_1_JDIF
 *   - Password   : 876543210
 *   - IP Portal  : http://192.168.4.1
 *   - Pemicu     : Tahan tombol BOOT (GPIO 0) 3 detik, atau otomatis terbuka jika WiFi gagal.
 * ============================================================
 */

#include <Arduino.h>
#include <WiFi.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <Preferences.h>
#include <SPI.h>
#include <Wire.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <LiquidCrystal_I2C.h>

// ============================================================
// 1. KONFIGURASI PIN HARDWARE
// ============================================================
#if defined(ARDUINO_NANO_ESP32)
  #define BOARD_NAME "Arduino Nano ESP32"
  constexpr uint8_t PIN_SS          = D10;
  constexpr uint8_t PIN_RST         = D9;
  constexpr uint8_t PIN_SCK         = D13;
  constexpr uint8_t PIN_MOSI        = D11;
  constexpr uint8_t PIN_MISO        = D12;
  constexpr uint8_t PIN_LCD_SDA     = A4;
  constexpr uint8_t PIN_LCD_SCL     = A5;
  constexpr uint8_t PIN_SERVO       = D6;
  constexpr uint8_t PIN_BUZZER      = D7;
  constexpr uint8_t PIN_LED         = D8;
  constexpr int8_t  PIN_LED_BUILTIN = -1;
  constexpr uint8_t PIN_BOOT        = D2;
#else
  #define BOARD_NAME "ESP32 DevKit 30-Pin"
  constexpr uint8_t PIN_SS          = 5;   // RC522 SDA/SS -> D5
  constexpr uint8_t PIN_RST         = 27;  // RC522 RST    -> D27 (utama, agar D22 bebas untuk I2C SCL LCD)
  constexpr uint8_t PIN_SCK         = 18;  // RC522 SCK    -> D18
  constexpr uint8_t PIN_MOSI        = 23;  // RC522 MOSI   -> D23
  constexpr uint8_t PIN_MISO        = 19;  // RC522 MISO   -> D19
  constexpr uint8_t PIN_LCD_SDA     = 21;  // LCD I2C SDA  -> D21
  constexpr uint8_t PIN_LCD_SCL     = 22;  // LCD I2C SCL  -> D22
  constexpr uint8_t PIN_SERVO       = 13;  // Servo SG90   -> D13
  constexpr uint8_t PIN_BUZZER      = 25;  // Buzzer (+)   -> D25
  constexpr uint8_t PIN_LED         = 26;  // LED (+)      -> D26
  constexpr int8_t  PIN_LED_BUILTIN = 2;   // LED biru bawaan board ESP32
  constexpr uint8_t PIN_BOOT        = 0;   // Tombol BOOT bawaan ESP32
#endif

// ============================================================
// 2. KONFIGURASI SERVO, HOTSPOT PORTAL, & TIMING
// ============================================================
constexpr int SERVO_CLOSE_ANGLE        = 0;
constexpr int SERVO_OPEN_ANGLE         = 90;
constexpr unsigned long SERVO_OPEN_MS  = 2500;
constexpr bool SERVO_OPEN_ON_ALL_200   = true; // True: setiap HTTP 200 (4 web & tap ulang demo) menggerakkan servo

const char* AP_SSID = "ESP32_1_JDIF";
const char* AP_PASS = "876543210";

const unsigned long SCAN_COOLDOWN_MS      = 2000;
const unsigned long HEARTBEAT_INTERVAL_MS = 2000;
const unsigned long HTTP_TIMEOUT_MS       = 10000;

// ============================================================
// 3. OBJEK GLOBAL & VARIABEL KONFIGURASI
// ============================================================
Preferences prefs;
DNSServer dnsServer;
WebServer webServer(80);
MFRC522 rfid(PIN_SS, PIN_RST);
LiquidCrystal_I2C* lcd = nullptr;
Servo gateServo;

// Konfigurasi Dinamis (disimpan permanen di NVS Flash)
String cfg_proj_name;
String cfg_wifi_ssid;
String cfg_wifi_pass;
String cfg_api_url;
String cfg_hb_url;
String cfg_dev_key;
String cfg_dev_id;
String cfg_op_mode;
bool cfg_buzzer_passive = false;

bool rc522Ready = false;
byte rc522Version = 0x00;
uint8_t activeRstPin = PIN_RST;
uint8_t lcdAddress = 0;
bool inPortalMode = false;

String lastUID = "";
unsigned long lastScanTime = 0;
unsigned long lastHeartbeatTime = 0;
unsigned long bootPressStartTime = 0;
unsigned long lastLedBlinkMs = 0;
bool ledState = false;

// Prototype Fungsi
void loadConfig();
void saveConfig(const String& proj, const String& ssid, const String& pass,
                const String& tapUrl, const String& hbUrl, const String& key, const String& id,
                const String& opMode, bool buzzerPassive);
void connectWiFi();
void startConfigPortal();
void handlePortalRoot();
void handlePortalSave();
void handlePortalTestHardware();
void handlePortalExit();
void initLcd();
void initServo();
void testRc522Connection();
String readUID();
void sendTapToServer(const String& uid);
void sendHeartbeat();
void setLed(bool on);
void blinkLed(uint8_t times, uint16_t onMs, uint16_t offMs);
void lcdShow(const String& line1, const String& line2 = "");
void showStandbyScreen();
void openGateServo();
void buzzerToneMs(uint16_t freqHz, uint16_t durMs);
void beepGateIn();
void beepGateOut();
void beepSuccess();
void beepDuplicate();
void beepError();
void beepPortal();
String extractJsonString(const String& json, const String& key);
int extractJsonBoolState(const String& json, const String& key);
String htmlEscape(const String& input);

// ============================================================
// 4. SETUP UTAMA
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(600);

    pinMode(PIN_BOOT, INPUT_PULLUP);
    pinMode(PIN_BUZZER, OUTPUT);
    digitalWrite(PIN_BUZZER, LOW);
    pinMode(PIN_LED, OUTPUT);
    if (PIN_LED_BUILTIN >= 0) {
        pinMode(PIN_LED_BUILTIN, OUTPUT);
    }
    setLed(false);

    // 1. Deteksi LCD I2C lebih dulu agar GPIO 22 aman jika LCD terpasang
    initLcd();
    lcdShow("Smart RW Reader", "Memulai alat...");

    // 2. Inisialisasi Servo ke posisi tutup (0 derajat)
    initServo();

    // 3. Inisialisasi SPI & RC522
#if defined(ARDUINO_NANO_ESP32)
    SPI.begin();
#else
    SPI.begin(PIN_SCK, PIN_MISO, PIN_MOSI, PIN_SS);
#endif
    testRc522Connection();

    // 4. Muat konfigurasi dari NVS Flash
    loadConfig();

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.println(F("   ESP32 UNIVERSAL RFID GATE (4-IN-1 KELOMPOK 1)  "));
    Serial.printf ("   Board          : %s\n", BOARD_NAME);
    Serial.printf ("   Projek Aktif   : %s (%s)\n", cfg_proj_name.c_str(), cfg_op_mode.c_str());
    Serial.printf ("   Target Tap URL : %s\n", cfg_api_url.c_str());
    Serial.printf ("   RC522 RST Pin  : GPIO %u (%s)\n", activeRstPin, rc522Ready ? "OK" : "ERROR");
    Serial.printf ("   LCD I2C        : %s\n", lcdAddress ? "Terdeteksi" : "Tidak terpasang (Mode Serial)");
    Serial.printf ("   Servo Pin      : GPIO %u | LED: GPIO %u | Buzzer: GPIO %u (%s)\n",
                   PIN_SERVO, PIN_LED, PIN_BUZZER, cfg_buzzer_passive ? "Pasif PWM" : "Aktif DC");
    Serial.println(F("--------------------------------------------------"));
    Serial.println(F("TIPS PORTAL CONFIG: Tahan tombol BOOT 3 detik atau"));
    Serial.println(F("connect ke Hotspot: ESP32_1_JDIF (192.168.4.1)"));
    Serial.println(F("=================================================="));

    // Jika tombol BOOT sedang ditahan saat menyala -> langsung masuk Portal
    if (digitalRead(PIN_BOOT) == LOW) {
        Serial.println(F("\n[PORTAL] Tombol BOOT terdeteksi saat start! Masuk Portal..."));
        beepPortal();
        startConfigPortal();
        return;
    }

    // Jika SSID kosong -> langsung masuk Portal
    if (cfg_wifi_ssid.length() == 0) {
        Serial.println(F("\n[PORTAL] Belum ada konfigurasi WiFi tersimpan. Membuka Portal..."));
        beepPortal();
        startConfigPortal();
        return;
    }

    // Hubungkan ke WiFi
    connectWiFi();
    showStandbyScreen();
}

// ============================================================
// 5. LOOP UTAMA
// ============================================================
void loop() {
    // 1. Deteksi tombol BOOT ditahan 3 detik untuk membuka Portal Konfigurasi
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

    // 2. Jika berada dalam mode Portal Konfigurasi
    if (inPortalMode) {
        dnsServer.processNextRequest();
        webServer.handleClient();
        if (millis() - lastLedBlinkMs >= 150) {
            lastLedBlinkMs = millis();
            setLed(!ledState);
        }
        delay(5);
        return;
    }

    // 3. Jaga koneksi WiFi (Auto-reconnect)
    if (WiFi.status() != WL_CONNECTED) {
        if (millis() - lastLedBlinkMs >= 250) {
            lastLedBlinkMs = millis();
            setLed(!ledState);
        }
        static unsigned long lastReconnect = 0;
        if (millis() - lastReconnect > 15000) {
            lastReconnect = millis();
            Serial.println(F("[WIFI] Terputus. Mencoba menyambung ulang..."));
            lcdShow("WiFi Terputus", "Menyambung...");
            WiFi.disconnect();
            delay(100);
            WiFi.begin(cfg_wifi_ssid.c_str(), cfg_wifi_pass.c_str());
        }
    } else if (!rc522Ready) {
        // WiFi nyambung tapi RC522 error -> LED kedip peringatan pelan
        if (millis() - lastLedBlinkMs >= 600) {
            lastLedBlinkMs = millis();
            setLed(!ledState);
        }
    } else {
        // Standby normal -> LED menyala mantap tanda siap scan
        if (!ledState) {
            setLed(true);
        }
    }

    // 4. Heartbeat berkala ke server (tanda alat ONLINE di website)
    if (WiFi.status() == WL_CONNECTED && millis() - lastHeartbeatTime >= HEARTBEAT_INTERVAL_MS) {
        lastHeartbeatTime = millis();
        sendHeartbeat();
    }

    // 5. Coba sambung ulang RC522 jika belum terdeteksi
    if (!rc522Ready) {
        static unsigned long lastRetry = 0;
        if (millis() - lastRetry > 3000) {
            lastRetry = millis();
            testRc522Connection();
            if (rc522Ready && WiFi.status() == WL_CONNECTED) {
                showStandbyScreen();
            }
        }
        return;
    }

    // 6. Deteksi kartu RFID / e-KTP (REQA + WUPA fallback)
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
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();

    if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
        delay(50);
        return;
    }

    // Anti-spam jika kartu sama ditempel terus-menerus
    if (uid == lastUID && (millis() - lastScanTime) < SCAN_COOLDOWN_MS) {
        return;
    }

    lastUID = uid;
    lastScanTime = millis();

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.printf ("💳 [SCAN] UID: %s (%d byte)\n", uid.c_str(), uidSize);
    Serial.printf ("🎯 [TARGET] Projek: %s\n", cfg_proj_name.c_str());
    Serial.println(F("=================================================="));

    sendTapToServer(uid);
    showStandbyScreen();
}

// ============================================================
// 6. MANAJEMEN KONFIGURASI NVS (PREFERENCES)
// ============================================================
void loadConfig() {
    prefs.begin("rfid_cfg", true);

    cfg_proj_name      = prefs.getString("proj",   "Dimas (Smart RW)");
    cfg_wifi_ssid      = prefs.getString("ssid",   "Poco F6505");
    cfg_wifi_pass      = prefs.getString("pass",   "dimas12345");
    cfg_api_url        = prefs.getString("tap",    "https://smartos-rw.unique-monsters.my.id/api/rfid/tap");
    cfg_hb_url         = prefs.getString("hb",     "https://smartos-rw.unique-monsters.my.id/api/rfid/heartbeat");
    cfg_dev_key        = prefs.getString("key",    "smartrw-rfid-device");
    cfg_dev_id         = prefs.getString("id",     "esp32-scanner-01");
    cfg_op_mode        = prefs.getString("opmode", "auto");
    cfg_buzzer_passive = prefs.getBool("buzpas",   false);

    if (cfg_op_mode != "auto" && cfg_op_mode != "gerbang") {
        cfg_op_mode = "auto";
    }

    prefs.end();
}

void saveConfig(const String& proj, const String& ssid, const String& pass,
                const String& tapUrl, const String& hbUrl, const String& key, const String& id,
                const String& opMode, bool buzzerPassive) {
    prefs.begin("rfid_cfg", false);

    prefs.putString("proj",   proj);
    prefs.putString("ssid",   ssid);
    prefs.putString("pass",   pass);
    prefs.putString("tap",    tapUrl);
    prefs.putString("hb",     hbUrl);
    prefs.putString("key",    key);
    prefs.putString("id",     id);
    prefs.putString("opmode", opMode == "gerbang" ? "gerbang" : "auto");
    prefs.putBool("buzpas",   buzzerPassive);

    prefs.end();
    Serial.println(F("[NVS] Konfigurasi berhasil disimpan permanen ke Flash ESP32!"));
}

// ============================================================
// 7. KONEKSI KE WIFI
// ============================================================
void connectWiFi() {
    WiFi.persistent(false);
    WiFi.disconnect(true);
    delay(100);
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);

    Serial.printf("[WIFI] Menghubungkan ke: %s ...\n", cfg_wifi_ssid.c_str());
    lcdShow("Konek WiFi...", cfg_wifi_ssid);
    WiFi.begin(cfg_wifi_ssid.c_str(), cfg_wifi_pass.c_str());

    unsigned long startAttempt = millis();
    unsigned long holdBootStart = 0;
    while (WiFi.status() != WL_CONNECTED && millis() - startAttempt < 25000) {
        if (digitalRead(PIN_BOOT) == LOW) {
            if (holdBootStart == 0) holdBootStart = millis();
            if (millis() - holdBootStart > 2000) {
                Serial.println(F("\n[PORTAL] Tombol BOOT ditekan saat konek WiFi! Membuka Portal..."));
                beepPortal();
                startConfigPortal();
                return;
            }
        } else {
            holdBootStart = 0;
        }
        setLed(!ledState);
        delay(250);
        Serial.print(F("."));
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println(F("\n[WIFI] Berhasil terhubung!"));
        Serial.printf("[WIFI] IP ESP32 : %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("[WIFI] RSSI     : %d dBm\n", WiFi.RSSI());

        IPAddress dnsGoogle(8, 8, 8, 8);
        IPAddress dnsCloudflare(1, 1, 1, 1);
        WiFi.config(INADDR_NONE, INADDR_NONE, INADDR_NONE, dnsGoogle, dnsCloudflare);

        setLed(rc522Ready);
        lcdShow("WiFi Terhubung!", WiFi.localIP().toString());
        if (rc522Ready) {
            beepSuccess();
        }
        delay(800);
        sendHeartbeat();
    } else {
        Serial.println(F("\n⚠️ [WIFI GAGAL] Tidak dapat terhubung dalam 25 detik!"));
        Serial.println(F("👉 Otomatis membuka Portal Konfigurasi Hotspot..."));
        lcdShow("WiFi Gagal!", "Buka Portal HP");
        beepPortal();
        startConfigPortal();
    }
}

// ============================================================
// 8. WEB PORTAL KONFIGURASI (CAPTIVE PORTAL VIA HP)
// ============================================================
void startConfigPortal() {
    inPortalMode = true;
    WiFi.disconnect();
    WiFi.mode(WIFI_AP);

    IPAddress apIP(192, 168, 4, 1);
    IPAddress netMsk(255, 255, 255, 0);
    WiFi.softAPConfig(apIP, apIP, netMsk);
    WiFi.softAP(AP_SSID, AP_PASS);

    lcdShow("Mode Portal HP", "192.168.4.1");

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.println(F("🚀 [PORTAL KONFIGURASI AKTIF]"));
    Serial.printf ("   Nama Hotspot ESP32 : %s\n", AP_SSID);
    Serial.printf ("   Password Hotspot   : %s\n", AP_PASS);
    Serial.println(F("   Alamat Web Portal  : http://192.168.4.1"));
    Serial.println(F("=================================================="));

    dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
    dnsServer.start(53, "*", apIP);

    webServer.on("/", HTTP_GET, handlePortalRoot);
    webServer.on("/save", HTTP_POST, handlePortalSave);
    webServer.on("/test-hw", HTTP_POST, handlePortalTestHardware);
    webServer.on("/exit", HTTP_GET, handlePortalExit);

    webServer.on("/generate_204", HTTP_GET, handlePortalRoot);
    webServer.on("/fwlink", HTTP_GET, handlePortalRoot);
    webServer.on("/connecttest.txt", HTTP_GET, handlePortalRoot);

    webServer.onNotFound([]() {
        webServer.sendHeader("Location", "http://192.168.4.1/", true);
        webServer.send(302, "text/plain", "");
    });

    webServer.begin();

    unsigned long portalStart = millis();
    const unsigned long PORTAL_TIMEOUT_MS = 300000; // 5 menit

    while (inPortalMode) {
        dnsServer.processNextRequest();
        webServer.handleClient();
        if (millis() - lastLedBlinkMs >= 150) {
            lastLedBlinkMs = millis();
            setLed(!ledState);
        }
        delay(5);

        if (millis() - portalStart > PORTAL_TIMEOUT_MS) {
            Serial.println(F("[PORTAL] Timeout 5 menit tercapai. Restarting ESP32..."));
            ESP.restart();
        }
    }
}

void handlePortalRoot() {
    char rc522Buf[48];
    if (rc522Ready) {
        snprintf(rc522Buf, sizeof(rc522Buf), "✅ OK (Chip 0x%02X, RST GPIO %u)", rc522Version, activeRstPin);
    } else {
        snprintf(rc522Buf, sizeof(rc522Buf), "❌ Belum terbaca (cek kabel 3.3V & SPI)");
    }

    char lcdBuf[40];
    if (lcdAddress != 0) {
        snprintf(lcdBuf, sizeof(lcdBuf), "✅ Terdeteksi (0x%02X)", lcdAddress);
    } else {
        snprintf(lcdBuf, sizeof(lcdBuf), "⚪ Tidak terpasang (opsional)");
    }

    String html = F("<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>"
        "<meta name='viewport' content='width=device-width,initial-scale=1.0'>"
        "<title>ESP32 Universal RFID Gate</title>"
        "<style>"
        "body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0fdf4;color:#1e293b;padding:15px;margin:0;}"
        ".card{background:#fff;border-radius:14px;padding:20px;max-width:460px;margin:auto;box-shadow:0 8px 24px rgba(0,0,0,0.08);}"
        "h2{color:#166534;margin-top:0;font-size:1.25rem;text-align:center;}"
        ".badge{background:#dcfce7;color:#15803d;padding:7px 12px;border-radius:12px;font-size:0.82rem;font-weight:600;margin-bottom:12px;text-align:center;}"
        ".hw{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;font-size:0.78rem;margin-bottom:14px;line-height:1.55;}"
        ".btn-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;}"
        ".p-btn{background:#f1f5f9;border:1px solid #cbd5e1;padding:10px 8px;border-radius:8px;font-size:0.84rem;font-weight:600;cursor:pointer;text-align:center;}"
        ".p-btn:hover{background:#22c55e;color:#fff;border-color:#16a34a;}"
        "label{font-size:0.82rem;font-weight:600;color:#475569;display:block;margin-top:10px;margin-bottom:4px;}"
        "input,select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.9rem;box-sizing:border-box;background:#fff;}"
        ".btn-submit{background:#16a34a;color:#fff;border:none;width:100%;padding:12px;border-radius:8px;font-size:0.98rem;font-weight:700;margin-top:18px;cursor:pointer;}"
        ".btn-test{background:#0284c7;color:#fff;border:none;width:100%;padding:10px;border-radius:8px;font-size:0.88rem;font-weight:600;margin-top:8px;cursor:pointer;}"
        ".btn-exit{background:#64748b;color:#fff;border:none;width:100%;padding:10px;border-radius:8px;font-size:0.88rem;font-weight:600;margin-top:8px;cursor:pointer;}"
        ".footer{font-size:0.74rem;color:#94a3b8;text-align:center;margin-top:14px;}"
        "</style></head><body><div class='card'>"
        "<h2>⚡ Universal RFID Gate</h2>"
        "<div class='badge'>Aktif Sekarang: <b>");
    html += htmlEscape(cfg_proj_name);
    html += F("</b></div>"
        "<div class='hw'>"
        "<b>Diagnosa Hardware:</b><br>"
        "• RC522 : ");
    html += rc522Buf;
    html += F("<br>• LCD I2C: ");
    html += lcdBuf;
    html += F("<br>• Pin GPIO: Buzzer=25, LED=26 (+2), Servo=13, SDA=21, SCL=22, RST=27"
        "<br>• Mode Kerja: <b>");
    html += (cfg_op_mode == "gerbang" ? "Gerbang Masuk/Keluar" : "Auto Ikuti Website");
    html += F("</b> | Buzzer: <b>");
    html += (cfg_buzzer_passive ? "Pasif (Nada)" : "Aktif (DC)");
    html += F("</b>"
        "</div>"
        "<label>PILIH SHORTCUT PROJEK (KELOMPOK 1):</label>"
        "<div class='btn-grid'>"
        "<button type='button' class='p-btn' onclick='pick(\"dimas\")'>🟢 Dimas (Smart RW)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"julius\")'>🔵 Julius (Guyub)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"ilham\")'>🟠 Ilham (Kawan)</button>"
        "<button type='button' class='p-btn' onclick='pick(\"fabian\")'>🟣 Fabian (E-Warga)</button>"
        "</div>"
        "<form action='/save' method='POST'>"
        "<label>Nama Projek / Pemilik:</label>"
        "<input type='text' id='proj' name='proj' value='");
    html += htmlEscape(cfg_proj_name);
    html += F("' required>"
        "<label>Nama WiFi / Hotspot HP (SSID):</label>"
        "<input type='text' id='ssid' name='ssid' value='");
    html += htmlEscape(cfg_wifi_ssid);
    html += F("' placeholder='Nama Hotspot HP Anda' required>"
        "<label>Password WiFi / Hotspot:</label>"
        "<input type='text' id='pass' name='pass' value='");
    html += htmlEscape(cfg_wifi_pass);
    html += F("' placeholder='Password Hotspot'>"
        "<label>Target URL Endpoint Tap:</label>"
        "<input type='text' id='tap' name='tap' value='");
    html += htmlEscape(cfg_api_url);
    html += F("' required>"
        "<label>Target URL Heartbeat (Status Online):</label>"
        "<input type='text' id='hb' name='hb' value='");
    html += htmlEscape(cfg_hb_url);
    html += F("' required>"
        "<label>Device Secret Key:</label>"
        "<input type='text' id='key' name='key' value='");
    html += htmlEscape(cfg_dev_key);
    html += F("' required>"
        "<label>Device ID:</label>"
        "<input type='text' id='id' name='id' value='");
    html += htmlEscape(cfg_dev_id);
    html += F("' required>"
        "<label>Mode Kerja Reader RFID:</label>"
        "<select id='opmode' name='opmode'>"
        "<option value='auto'");
    if (cfg_op_mode != "gerbang") html += F(" selected");
    html += F(">Auto Ikuti Website (Pendaftaran KTP / Rapat / Gerbang)</option>"
        "<option value='gerbang'");
    if (cfg_op_mode == "gerbang") html += F(" selected");
    html += F(">Khusus Gerbang Masuk/Keluar Servo (Selalu Aktif)</option>"
        "</select>"
        "<label>Jenis Buzzer Terpasang (Pin D25):</label>"
        "<select id='buztype' name='buztype'>"
        "<option value='active'");
    if (!cfg_buzzer_passive) html += F(" selected");
    html += F(">Buzzer Aktif (DC 3.3V/5V - Umumnya berstiker putih)</option>"
        "<option value='passive'");
    if (cfg_buzzer_passive) html += F(" selected");
    html += F(">Buzzer Pasif (Nada Tone PWM - Bawah PCB terbuka)</option>"
        "</select>"
        "<button type='submit' class='btn-submit'>💾 SIMPAN & REBOOT ALAT</button>"
        "</form>"
        "<form action='/test-hw' method='POST'>"
        "<button type='submit' class='btn-test'>🔊 Test LED + Buzzer + Servo</button>"
        "</form>"
        "<form action='/exit' method='GET'>"
        "<button type='submit' class='btn-exit'>❌ Batal & Keluar</button>"
        "</form>"
        "<div class='footer'>ESP32 JDIF Multi-Project Reader &bull; Portal 192.168.4.1</div>"
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
    String proj    = webServer.arg("proj");
    String ssid    = webServer.arg("ssid");
    String pass    = webServer.arg("pass");
    String tap     = webServer.arg("tap");
    String hb      = webServer.arg("hb");
    String key     = webServer.arg("key");
    String id      = webServer.arg("id");
    String opMode  = webServer.arg("opmode");
    String buzType = webServer.arg("buztype");

    saveConfig(proj, ssid, pass, tap, hb, key, id, opMode, buzType == "passive");

    String resp = F("<!DOCTYPE html><html><head><meta charset='UTF-8'>"
        "<meta name='viewport' content='width=device-width,initial-scale=1.0'>"
        "<title>Tersimpan</title>"
        "<style>body{font-family:sans-serif;text-align:center;padding:40px 20px;background:#f0fdf4;color:#166534;}</style>"
        "</head><body>"
        "<h1>🎉 Konfigurasi Tersimpan!</h1>"
        "<p>Alat sedang me-restart ke Projek: <b>");
    resp += htmlEscape(proj);
    resp += F("</b></p>"
        "<p>Silakan putuskan sambungan dari WiFi ESP32 dan buka website projek Anda.</p>"
        "<script>setTimeout(function(){window.close();}, 4000);</script>"
        "</body></html>");

    webServer.send(200, "text/html", resp);
    delay(1200);
    ESP.restart();
}

void handlePortalTestHardware() {
    lcdShow("Test Hardware...", "LED+Buz+Servo");
    setLed(true);
    beepGateIn();
    gateServo.write(SERVO_OPEN_ANGLE);
    delay(800);
    beepGateOut();
    gateServo.write(SERVO_CLOSE_ANGLE);
    setLed(false);
    lcdShow("Mode Portal HP", "192.168.4.1");

    webServer.sendHeader("Location", "/", true);
    webServer.send(302, "text/plain", "");
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
    delay(800);
    inPortalMode = false;
    ESP.restart();
}

// ============================================================
// 9. INISIALISASI LCD, SERVO & DIAGNOSIS RC522
// ============================================================
void initLcd() {
    Wire.begin(PIN_LCD_SDA, PIN_LCD_SCL);
    const uint8_t candidates[] = {0x27, 0x3F};
    for (uint8_t addr : candidates) {
        Wire.beginTransmission(addr);
        if (Wire.endTransmission() == 0) {
            lcdAddress = addr;
            break;
        }
    }

    if (lcdAddress != 0) {
        lcd = new LiquidCrystal_I2C(lcdAddress, 16, 2);
        lcd->init();
        lcd->backlight();
        Serial.printf("[I2C] LCD 16x2 terdeteksi di alamat 0x%02X\n", lcdAddress);
    } else {
        Serial.println(F("[I2C] LCD 16x2 tidak terpasang (berjalan di Serial + LED + Buzzer)"));
    }
}

void initServo() {
    ESP32PWM::allocateTimer(0);
    gateServo.setPeriodHertz(50);
    gateServo.attach(PIN_SERVO, 500, 2400);
    gateServo.write(SERVO_CLOSE_ANGLE);
}

void testRc522Connection() {
    activeRstPin = PIN_RST;
    rfid = MFRC522(PIN_SS, activeRstPin);
    rfid.PCD_Init();
    delay(40);
    byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);

#if !defined(ARDUINO_NANO_ESP32)
    // Fallback ke pin 22 HANYA jika LCD I2C (SCL=22) tidak sedang terpasang
    if ((v == 0x00 || v == 0xFF) && lcdAddress == 0) {
        activeRstPin = 22;
        rfid = MFRC522(PIN_SS, activeRstPin);
        rfid.PCD_Init();
        delay(40);
        v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    }
#endif

    rc522Version = v;
    if (v == 0x00 || v == 0xFF) {
        rc522Ready = false;
        Serial.println(F("\n⚠️  [RC522 ERROR] Modul RC522 tidak terdeteksi (0x00 / 0xFF)!"));
        Serial.println(F("   Pastikan 3.3V terpasang benar (BUKAN 5V) dan kabel SPI (D5,D18,D19,D23,D27) kencang."));
    } else {
        rc522Ready = true;
        rfid.PCD_SetAntennaGain(rfid.RxGain_43dB);
        Serial.printf("✅ Modul RC522 TERHUBUNG (Chip 0x%02X, RST GPIO %u)\n", v, activeRstPin);
    }
}

// ============================================================
// 10. BACA UID KARTU
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
// 11. KIRIM HASIL SCAN KE SERVER AKTIF
// ============================================================
void sendTapToServer(const String& uid) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println(F("⚠️  [WIFI OFFLINE] Kartu terbaca, tapi ESP32 belum terhubung ke WiFi/Hotspot."));
        lcdShow("WiFi Terputus!", "UID:" + uid.substring(0, min<unsigned int>(12, uid.length())));
        beepError();
        blinkLed(4, 100, 90);
        delay(1000);
        return;
    }

    setLed(false);
    lcdShow("Memproses Tap...", uid);

    WiFiClient plainClient;
    WiFiClientSecure secureClient;
    HTTPClient http;
    http.setTimeout(HTTP_TIMEOUT_MS);

    bool began = false;
    if (cfg_api_url.startsWith("https://")) {
        secureClient.setInsecure();
        secureClient.setTimeout(10);
        began = http.begin(secureClient, cfg_api_url);
    } else {
        began = http.begin(plainClient, cfg_api_url);
    }

    if (!began) {
        lcdShow("URL API Error!", "Cek Web Portal");
        beepError();
        blinkLed(4, 100, 90);
        delay(1200);
        return;
    }

    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + cfg_dev_key);
    http.addHeader("X-Device-Key", cfg_dev_key);

    String payload = "{\"uid\":\"" + uid + "\",\"device_id\":\"" + cfg_dev_id + "\"";
    if (cfg_op_mode == "gerbang") {
        payload += ",\"mode\":\"gerbang\"";
    }
    payload += "}";

    Serial.printf("[HTTP] POST %s | Payload: %s\n", cfg_api_url.c_str(), payload.c_str());
    int httpCode = http.POST(payload);
    String response = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);
    http.end();

    Serial.printf("[HTTP] Kode Response: %d | Body: %s\n", httpCode, response.c_str());

    if (httpCode == 200) {
        String status    = extractJsonString(response, "status");
        String feedback  = extractJsonString(response, "feedback");
        String userName  = extractJsonString(response, "user_name");
        String direction = extractJsonString(response, "direction");
        String buzzerCmd = extractJsonString(response, "buzzer");
        int openGateVal  = extractJsonBoolState(response, "open_gate");
        bool isDup       = (feedback == "duplicate")
                        || (status == "already_present")
                        || (status == "ktp_already_verified")
                        || (status == "koperasi_already_verified");

        String l1 = userName.length() > 0 ? ("Halo, " + userName) : "Tap Berhasil!";
        String l2 = "Akses Diterima";
        if (status == "gate_opened" || direction == "masuk" || direction == "keluar") {
            String dirPrefix = (direction == "keluar") ? "KELUAR: " : "MASUK: ";
            l1 = dirPrefix + (userName.length() > 0 ? userName : "Warga");
            l2 = "Gerbang Terbuka";
        } else if (status == "present") l2 = "Hadir Tercatat!";
        else if (status == "already_present") l2 = "Sudah Hadir";
        else if (status == "registered" || status == "ktp_registered" || status == "koperasi_registered") l2 = "Kartu Terdaftar!";
        else if (status == "ktp_verified" || status == "ktp_already_verified") l2 = "KTP Terverifikasi";
        else if (status == "koperasi_verified" || status == "koperasi_already_verified") l2 = "Koperasi OK";

        lcdShow(l1, l2);
        Serial.println(F("🎉 [SUKSES] Data kartu berhasil diterima server!\n"));

        setLed(true);
        if (buzzerCmd == "gate_in" || (status == "gate_opened" && direction == "masuk")) {
            beepGateIn();
        } else if (buzzerCmd == "gate_out" || (status == "gate_opened" && direction == "keluar")) {
            beepGateOut();
        } else if (isDup) {
            beepDuplicate();
        } else {
            beepSuccess();
        }

        bool shouldOpenServo = (openGateVal == 1)
                            || (openGateVal == -1 && (SERVO_OPEN_ON_ALL_200 || !isDup));
        if (shouldOpenServo) {
            openGateServo();
        } else {
            delay(1200);
        }
    } else {
        String l1 = "Tap Ditolak!";
        String l2 = "Kode HTTP: " + String(httpCode);

        if (httpCode == 422 || httpCode == 403) {
            if (response.indexOf("luar wilayah") >= 0) {
                l1 = "Bukan Wilayah!";
                l2 = "Ditolak Gerbang";
            } else if (response.indexOf("belum terverifikasi") >= 0) {
                l1 = "KTP Blm Verif!";
                l2 = "Hubungi Admin RT";
            } else if (response.indexOf("tidak aktif") >= 0) {
                l1 = "Akun Nonaktif";
                l2 = "Hubungi Admin";
            } else if (response.indexOf("warga lain") >= 0) {
                l1 = "Kartu Dipakai!";
                l2 = "Milik Warga Lain";
            } else if (response.indexOf("kedaluwarsa") >= 0) {
                l1 = "Sesi Kedaluwarsa";
                l2 = "Ulangi di Web";
            } else if (response.indexOf("belum terdaftar") >= 0 || response.indexOf("tidak terdaftar") >= 0) {
                l1 = "Belum Terdaftar!";
                l2 = "Daftar di Web";
            } else if (httpCode == 403) {
                l1 = "Akses Ditolak";
                l2 = "Cek Izin/Key";
            } else {
                l1 = "Data Ditolak";
                l2 = "Cek Layar Web";
            }
        } else if (httpCode == 409) {
            l1 = "Kegiatan Tutup";
            l2 = "Belum/Selesai";
        } else if (httpCode == 401) {
            l1 = "Key/Token Salah";
            l2 = "Cek Device Key";
        }

        lcdShow(l1, l2);
        beepError();
        blinkLed(4, 100, 90);
        delay(1000);
    }
}

// ============================================================
// 12. HEARTBEAT KE SERVER (STATUS ONLINE REALTIME)
// ============================================================
void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED || cfg_hb_url.length() == 0) return;

    WiFiClient plainClient;
    WiFiClientSecure secureClient;
    HTTPClient http;
    http.setTimeout(6000);

    bool began = false;
    if (cfg_hb_url.startsWith("https://")) {
        secureClient.setInsecure();
        secureClient.setTimeout(6);
        began = http.begin(secureClient, cfg_hb_url);
    } else {
        began = http.begin(plainClient, cfg_hb_url);
    }
    if (!began) return;

    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + cfg_dev_key);
    http.addHeader("X-Device-Key", cfg_dev_key);

    String payload = "{\"device_id\":\"" + cfg_dev_id + "\",\"ip\":\"" + WiFi.localIP().toString() + "\"}";

    int code = http.POST(payload);
    if (code == 200) {
        static unsigned long hbCount = 0;
        if (++hbCount % 5 == 0) {
            Serial.print(F("."));
        }
        // Kedipan singkat tanda heartbeat terkirim (jika standby normal)
        if (rc522Ready) {
            setLed(false);
            delay(35);
            setLed(true);
        }
    } else {
        Serial.printf("\n[HEARTBEAT] Gagal (Kode: %d | Info: %s)\n", code, http.errorToString(code).c_str());
    }
    http.end();
}

// ============================================================
// 13. UTILITAS HARDWARE (LED, BUZZER, SERVO, LCD, JSON)
// ============================================================
void setLed(bool on) {
    ledState = on;
    digitalWrite(PIN_LED, on ? HIGH : LOW);
    if (PIN_LED_BUILTIN >= 0) {
        digitalWrite(PIN_LED_BUILTIN, on ? HIGH : LOW);
    }
}

void blinkLed(uint8_t times, uint16_t onMs, uint16_t offMs) {
    for (uint8_t i = 0; i < times; i++) {
        setLed(true);
        delay(onMs);
        setLed(false);
        if (i + 1 < times) {
            delay(offMs);
        }
    }
}

void openGateServo() {
    gateServo.write(SERVO_OPEN_ANGLE);
    delay(SERVO_OPEN_MS);
    gateServo.write(SERVO_CLOSE_ANGLE);
}

void lcdShow(const String& line1, const String& line2) {
    if (!lcd) return;

    auto pad16 = [](String text) -> String {
        if (text.length() > 16) text = text.substring(0, 16);
        while (text.length() < 16) text += ' ';
        return text;
    };

    lcd->setCursor(0, 0);
    lcd->print(pad16(line1));
    lcd->setCursor(0, 1);
    lcd->print(pad16(line2));
}

void showStandbyScreen() {
    String top = cfg_proj_name.length() > 0 ? cfg_proj_name : "Smart RW RFID";
    if (!rc522Ready) {
        lcdShow(top, "RC522 Error! Cek");
    } else if (cfg_op_mode == "gerbang") {
        lcdShow(top, "Gerbang: Tap KTP");
    } else {
        lcdShow(top, "Tap Kartu Anda");
    }
}

void buzzerToneMs(uint16_t freqHz, uint16_t durMs) {
    if (cfg_buzzer_passive) {
        tone(PIN_BUZZER, freqHz);
        delay(durMs);
        noTone(PIN_BUZZER);
        pinMode(PIN_BUZZER, OUTPUT);
        digitalWrite(PIN_BUZZER, LOW);
        return;
    }

    digitalWrite(PIN_BUZZER, HIGH);
    delay(durMs);
    digitalWrite(PIN_BUZZER, LOW);
}

void beepGateIn() {
    buzzerToneMs(2300, 95);
    delay(65);
    buzzerToneMs(2900, 120);
}

void beepGateOut() {
    buzzerToneMs(2100, 220);
    delay(55);
    buzzerToneMs(1650, 140);
}

void beepSuccess() {
    buzzerToneMs(2200, 100);
    delay(80);
    buzzerToneMs(2200, 100);
}

void beepDuplicate() {
    buzzerToneMs(1800, 90);
    delay(70);
    buzzerToneMs(1800, 90);
}

void beepError() {
    for (int i = 0; i < 3; i++) {
        buzzerToneMs(950, 150);
        if (i < 2) delay(90);
    }
}

void beepPortal() {
    buzzerToneMs(1500, 320);
    delay(100);
    buzzerToneMs(1500, 320);
}

String extractJsonString(const String& json, const String& key) {
    String pattern = "\"" + key + "\"";
    int keyIdx = json.indexOf(pattern);
    if (keyIdx < 0) return "";
    int colonIdx = json.indexOf(':', keyIdx + pattern.length());
    if (colonIdx < 0) return "";

    int pos = colonIdx + 1;
    while (pos < (int) json.length() && (json[pos] == ' ' || json[pos] == '\t')) pos++;
    if (pos >= (int) json.length() || json[pos] != '"') return "";

    int startQuote = pos;
    int endQuote = json.indexOf('"', startQuote + 1);
    while (endQuote > 0 && json[endQuote - 1] == '\\') {
        endQuote = json.indexOf('"', endQuote + 1);
    }
    if (endQuote < 0) return "";
    return json.substring(startQuote + 1, endQuote);
}

int extractJsonBoolState(const String& json, const String& key) {
    String pattern = "\"" + key + "\"";
    int keyIdx = json.indexOf(pattern);
    if (keyIdx < 0) return -1;
    int colonIdx = json.indexOf(':', keyIdx + pattern.length());
    if (colonIdx < 0) return -1;

    int pos = colonIdx + 1;
    while (pos < (int) json.length() && (json[pos] == ' ' || json[pos] == '\t')) pos++;
    if (json.startsWith("true", pos)) return 1;
    if (json.startsWith("false", pos)) return 0;
    return -1;
}

String htmlEscape(const String& input) {
    String out = input;
    out.replace("&", "&amp;");
    out.replace("'", "&#39;");
    out.replace("\"", "&quot;");
    out.replace("<", "&lt;");
    out.replace(">", "&gt;");
    return out;
}
