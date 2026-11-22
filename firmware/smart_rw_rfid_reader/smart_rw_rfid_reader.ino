/*
 * ============================================================
 * Smart RW - Terminal Registrasi & Verifikasi e-KTP (ESP32)
 * ============================================================
 * Fitur:
 *   - Membaca UID e-KTP / kartu RFID / tag RFID (13.56 MHz)
 *   - Otomatis kirim UID ke server Smart RW tanpa perlu token manual
 *   - Diagnosis otomatis koneksi modul RC522 di Serial Monitor
 *   - Auto-detect pin RST (GPIO 27 atau GPIO 22)
 *   - Kompatibel dengan ESP32 DevKit (30/38 pin) & Arduino Nano ESP32
 *
 * Wiring RC522 ke ESP32 Standar (DevKit 30/38 pin):
 *   RC522 Pin  | ESP32 GPIO | Keterangan
 *   -----------|------------|--------------------------------
 *   SDA (SS)   | GPIO 5     | SPI Chip Select
 *   SCK        | GPIO 18    | SPI Clock
 *   MOSI       | GPIO 23    | SPI Master Out
 *   MISO       | GPIO 19    | SPI Master In
 *   RST        | GPIO 27    | Reset Pin (Bisa juga GPIO 22)
 *   GND        | GND        | Ground
 *   3.3V (VCC) | 3.3V       | ⚠️ WAJIB 3.3V! JANGAN KE 5V / VIN!
 *
 * Pin Buzzer Opsional:
 *   Buzzer (+) -> GPIO 25 | Buzzer (-) -> GND
 * ============================================================
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

// ============================================================
// 1. KONFIGURASI WIFI & SERVER SMART RW
// ============================================================
// Hotspot HP Anda:
const char* WIFI_SSID     = "Poco F6505";
const char* WIFI_PASSWORD = "dimas12345";

// Endpoint API Server Smart RW
const char* API_URL       = "https://smartos-rw.unique-monsters.my.id/api/rfid/tap";

// Master Device Key (Hardware Key permanen — bebas token manual)
const char* DEVICE_KEY    = "smartrw-rfid-device";

// ============================================================
// 2. KONFIGURASI PIN SPI RC522
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
  constexpr uint8_t PIN_RST  = 27; // Default pin 27 (Auto-fallback ke 22)
  constexpr uint8_t PIN_SCK  = 18;
  constexpr uint8_t PIN_MOSI = 23;
  constexpr uint8_t PIN_MISO = 19;
#endif

// Pin Buzzer Opsional (Active HIGH)
constexpr uint8_t PIN_BUZZER = 25;

// ============================================================
// 3. VARIABEL GLOBAL & OBJEK
// ============================================================
MFRC522 rfid(PIN_SS, PIN_RST);
String lastUID = "";
unsigned long lastScanTime = 0;
const unsigned long SCAN_COOLDOWN_MS = 2500; // Jeda 2.5 detik untuk kartu yang sama

// Prototype Fungsi
String readUID();
void sendTapToServer(const String& uid);
void testRc522Connection();
void beepSuccess();
void beepError();

// ============================================================
// 4. SETUP
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(1000);

    Serial.println();
    Serial.println(F("=================================================="));
    Serial.println(F("   SMART RW - ESP32 RFID / e-KTP TERMINAL         "));
    Serial.printf ("   Board: %s\n", BOARD_NAME);
    Serial.println(F("=================================================="));

    // Inisialisasi Buzzer
    pinMode(PIN_BUZZER, OUTPUT);
    digitalWrite(PIN_BUZZER, LOW);

    // Inisialisasi SPI & Modul RFID
#if defined(ARDUINO_NANO_ESP32)
    SPI.begin();
#else
    SPI.begin(PIN_SCK, PIN_MISO, PIN_MOSI, PIN_SS);
#endif
    rfid.PCD_Init();
    delay(50);

    // Diagnosis koneksi modul RC522
    testRc522Connection();

    // Koneksi ke WiFi Hotspot
    Serial.printf("\n[WIFI] Menghubungkan ke: %s ...\n", WIFI_SSID);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int retries = 0;
    while (WiFi.status() != WL_CONNECTED && retries < 25) {
        delay(500);
        Serial.print(F("."));
        retries++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println(F("\n[WIFI] Berhasil terhubung!"));
        Serial.printf("[WIFI] IP Address ESP32: %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("[WIFI] Kualitas Sinyal: %d dBm\n", WiFi.RSSI());
        beepSuccess();
    } else {
        Serial.println(F("\n⚠️  [WIFI GAGAL] Tidak dapat terhubung ke WiFi/Hotspot."));
        Serial.println(F("    Pastikan Hotspot HP aktif dan password benar."));
        Serial.println(F("    Alat tetap dapat mendeteksi kartu secara offline di Serial Monitor."));
    }

    Serial.println(F("\n=================================================="));
    Serial.println(F("STATUS: SIAP MEMBACA KARTU e-KTP!"));
    Serial.println(F(">>> Buka web: smartos-rw.unique-monsters.my.id/kartu-rfid"));
    Serial.println(F(">>> Klik tombol 'Buka Sesi Tap Mandiri' di web,"));
    Serial.println(F(">>> lalu dekatkan kartu e-KTP ke sensor RFID."));
    Serial.println(F("==================================================\n"));
}

// ============================================================
// 5. LOOP PEMBACAAN KARTU
// ============================================================
void loop() {
    // 1. Cek Koneksi WiFi (Auto-Reconnect)
    if (WiFi.status() != WL_CONNECTED) {
        static unsigned long lastReconnectAttempt = 0;
        if (millis() - lastReconnectAttempt > 10000) {
            lastReconnectAttempt = millis();
            Serial.println(F("[WIFI] Terputus! Mencoba menyambung kembali..."));
            WiFi.reconnect();
        }
    }

    // 2. Cek apakah ada kartu e-KTP / RFID di sensor
    if (!rfid.PICC_IsNewCardPresent()) {
        delay(20);
        return;
    }
    if (!rfid.PICC_ReadCardSerial()) {
        delay(20);
        return;
    }

    // 3. Baca nomor seri (UID) kartu
    String uid = readUID();

    // Validasi panjang UID
    uint8_t uidSize = rfid.uid.size;
    if (uidSize != 4 && uidSize != 7 && uidSize != 10) {
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        delay(50);
        return;
    }

    // Jeda anti-spam jika kartu yang sama ditempel terus
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
    Serial.println(F("=================================================="));

    // 4. Kirim UID ke server Smart RW
    sendTapToServer(uid);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}

// ============================================================
// 6. DIAGNOSIS KONEKSI HARDWARE RC522
// ============================================================
void testRc522Connection() {
    byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);

#if !defined(ARDUINO_NANO_ESP32)
    // Coba fallback pin RST 22 jika pin 27 tidak merespon
    if (v == 0x00 || v == 0xFF) {
        Serial.println(F("[RC522] RST di pin 27 belum merespon, mencoba pin 22..."));
        rfid = MFRC522(PIN_SS, 22);
        rfid.PCD_Init();
        delay(50);
        v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    }
#endif

    Serial.printf("[RC522] Versi Chip Firmware: 0x%02X\n", v);

    if (v == 0x00 || v == 0xFF) {
        Serial.println();
        Serial.println(F("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"));
        Serial.println(F("❌ PERINGATAN KERAS: Modul RC522 TIDAK TERBACA OLEH ESP32!"));
        Serial.println(F("   Penyebab utama modul tidak terbaca:"));
        Serial.println(F("   1. Kaki 8 pin RC522 BELUM DISOLDER dengan timah panas."));
        Serial.println(F("   2. VCC wajib ke pin 3.3V (JANGAN KE 5V/VIN!)."));
        Serial.println(F("   3. Pin SPI: SDA->GPIO 5, SCK->18, MOSI->23, MISO->19, RST->27/22."));
        Serial.println(F("   4. Periksa apakah ada kabel jumper yang kendor."));
        Serial.println(F("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n"));
        beepError();
    } else {
        rfid.PCD_SetAntennaGain(rfid.RxGain_max); // Sensitivitas maksimal antena untuk e-KTP
        Serial.println(F("✅ Modul RC522 BERHASIL TERHUBUNG DENGAN BAIK!\n"));
    }
}

// ============================================================
// 7. FORMAT UID DARI BYTE KE STRING HEX
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
// 8. KIRIM HASIL SCAN KE SERVER SMART RW
// ============================================================
void sendTapToServer(const String& uid) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println(F("⚠️  [WIFI OFFLINE] Kartu terbaca di alat, tetapi TIDAK terkirim ke web"));
        Serial.println(F("    karena WiFi belum tersambung ke Hotspot HP."));
        beepError();
        return;
    }

    WiFiClientSecure client;
    client.setInsecure(); // Memudahkan koneksi HTTPS tanpa manajemen sertifikat rumit

    HTTPClient http;
    http.begin(client, API_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + String(DEVICE_KEY));
    http.setTimeout(10000);

    String payload = "{\"uid\":\"" + uid + "\"}";

    Serial.printf("[HTTP] Mengirim UID %s ke server...\n", uid.c_str());
    int httpCode = http.POST(payload);
    String response = (httpCode > 0) ? http.getString() : http.errorToString(httpCode);

    Serial.printf("[HTTP] Kode Response: %d\n", httpCode);

    if (httpCode == 200) {
        Serial.printf("[HTTP] Respons Server: %s\n", response.c_str());

        if (response.indexOf("ktp_registered") >= 0) {
            Serial.println(F("\n🎉 [SUKSES BESAR] e-KTP fisik berhasil didaftarkan ke akun warga!"));
            Serial.println(F("🎉 Status di website kartu-rfid otomatis berubah 'Terdaftar Resmi'.\n"));
            beepSuccess();
        } else if (response.indexOf("ktp_verified") >= 0 || response.indexOf("ktp_already_verified") >= 0) {
            Serial.println(F("\n✅ [TERVERIFIKASI] e-KTP ini sudah resmi terdaftar di RT dan sistem.\n"));
            beepSuccess();
        } else if (response.indexOf("present") >= 0) {
            Serial.println(F("\n✅ [ABSENSI HADIR] Kehadiran rapat/kegiatan warga berhasil dicatat!\n"));
            beepSuccess();
        } else {
            Serial.printf("\n[OK] Respon: %s\n", response.c_str());
            beepSuccess();
        }
    } else if (httpCode == 422) {
        Serial.printf("[HTTP] Respon Validasi Server: %s\n", response.c_str());

        if (response.indexOf("terdaftar di akun") >= 0 || response.indexOf("warga lain") >= 0) {
            Serial.println(F("\n⚠️  [KARTU SUDAH DIGUNAKAN WARGA LAIN]"));
            Serial.println(F("⚠️  Fisik kartu e-KTP ini sudah terdaftar atas nama warga lain!"));
            Serial.println(F("⚠️  1 kartu fisik e-KTP hanya boleh terdaftar untuk 1 orang warga."));
            Serial.println(F("⚠️  Silakan gunakan kartu e-KTP milik Anda sendiri.\n"));
        } else if (response.indexOf("kedaluwarsa") >= 0) {
            Serial.println(F("\n⚠️  [SESI TAP KEDALUWARSA]"));
            Serial.println(F("⚠️  Waktu pendaftaran 3 menit telah habis."));
            Serial.println(F("⚠️  Silakan buka web dan klik 'Buka Sesi Tap Mandiri' lagi.\n"));
        } else if (response.indexOf("belum terdaftar") >= 0) {
            Serial.println(F("\n⚠️  [KARTU BELUM TERDAFTAR]"));
            Serial.println(F("⚠️  Kartu ini belum terdaftar di sistem."));
            Serial.println(F("⚠️  Untuk mendaftar, buka web smartos-rw.unique-monsters.my.id/kartu-rfid"));
            Serial.println(F("⚠️  klik 'Buka Sesi Tap Mandiri', lalu tempelkan kartu ini lagi.\n"));
        }
        beepError();
    } else {
        Serial.printf("[HTTP ERROR] Kode: %d | Body: %s\n", httpCode, response.c_str());
        beepError();
    }

    http.end();
}

// ============================================================
// 9. NADA BUZZER (OPSIONAL)
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