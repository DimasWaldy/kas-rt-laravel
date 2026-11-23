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
// Nama Hotspot HP dan Password Anda:
const char* WIFI_SSID     = "Poco F6505";
const char* WIFI_PASSWORD = "dimas12345";

// Endpoint API Server Smart RW (Mendukung /tap dan /scan)
const char* API_URL       = "https://smartos-rw.unique-monsters.my.id/api/rfid/tap";
const char* HEARTBEAT_URL = "https://smartos-rw.unique-monsters.my.id/api/rfid/heartbeat";

// Master Device Key (Hardware Key permanen — bebas token manual)
const char* DEVICE_KEY    = "smartrw-rfid-device";
const char* DEVICE_ID     = "esp32-scanner-01";

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
  constexpr uint8_t PIN_RST  = 22; // Pin 22 adalah standar wiring DevKit ESP32
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
bool rc522Ready = false;
String lastUID = "";
unsigned long lastScanTime = 0;
unsigned long lastHeartbeatTime = 0;
const unsigned long SCAN_COOLDOWN_MS = 2000; // Jeda 2 detik untuk kartu yang sama
const unsigned long HEARTBEAT_INTERVAL_MS = 2000; // Heartbeat setiap 2 detik untuk stabilitas koneksi HTTPS tanpa overload heap

// Prototype Fungsi
String readUID();
void sendTapToServer(const String& uid);
void sendHeartbeat();
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

#if !defined(ARDUINO_NANO_ESP32)
    // Tarik kedua pin RST (GPIO 22 dan 27) ke HIGH agar modul keluar dari power-down mode
    pinMode(22, OUTPUT);
    digitalWrite(22, HIGH);
    pinMode(27, OUTPUT);
    digitalWrite(27, HIGH);
    delay(20);
#endif

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

    // Reset dan konfigurasi WiFi Station
    WiFi.persistent(false);
    WiFi.disconnect(true);
    delay(100);
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);

    // Event handler untuk diagnosis penyebab gagal koneksi WiFi
    WiFi.onEvent([](WiFiEvent_t event, WiFiEventInfo_t info) {
        if (event == ARDUINO_EVENT_WIFI_STA_DISCONNECTED) {
            uint8_t reason = info.wifi_sta_disconnected.reason;
            Serial.printf("\n[WIFI GAGAL] Terputus / Gagal Terhubung (Alasan Kode: %d)\n", reason);
            if (reason == 201) { // WIFI_REASON_NO_AP_FOUND
                Serial.println(F("👉 PENYEBAB: Sinyal Hotspot Tidak Terdeteksi (NO_AP_FOUND)!"));
                Serial.println(F("   1. Pastikan Hotspot HP sudah AKTIF dan SSID 'Poco F6505' sudah tepat."));
                Serial.println(F("   2. ⚠️ PENTING: Pada HP Poco / Xiaomi, ganti 'Pita AP' ke '2.4 GHz'!"));
                Serial.println(F("      (Buka Setelan HP -> Hotspot Portabel -> Atur Hotspot -> Pita AP -> Pilih 2,4 GHz)."));
                Serial.println(F("      ESP32 TIDAK BISA mendeteksi pita 5.0 GHz / Wi-Fi 6."));
                Serial.println(F("   3. Matikan opsi 'Hotspot Wi-Fi 6' dan aktifkan 'Maksimalkan Kompatibilitas'."));
            } else if (reason == 202 || reason == 15) { // AUTH_FAIL / 4WAY_HANDSHAKE_TIMEOUT
                Serial.println(F("👉 PENYEBAB: Password Salah atau Keamanan WPA3 Tidak Didukung!"));
                Serial.println(F("   Ubah keamanan Hotspot HP dari 'WPA3' menjadi 'WPA2-Personal'."));
            } else if (reason == 200) {
                Serial.println(F("👉 PENYEBAB: Sinyal terlalu jauh atau terhalang. Dekatkan ESP32 ke HP."));
            }
        }
    });

    // Koneksi ke WiFi Hotspot
    Serial.printf("[WIFI] Menghubungkan ke: %s ...\n", WIFI_SSID);
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
        Serial.printf("[WIFI] Gateway: %s\n", WiFi.gatewayIP().toString().c_str());
        Serial.printf("[WIFI] DNS Asli HP: %s\n", WiFi.dnsIP().toString().c_str());
        Serial.printf("[WIFI] Kualitas Sinyal: %d dBm\n", WiFi.RSSI());

        // Atur DNS Publik Google (8.8.8.8) dan Cloudflare (1.1.1.1)
        // untuk mengatasi DNS proxy Hotspot Android yang sering menolak query domain
        IPAddress dnsGoogle(8, 8, 8, 8);
        IPAddress dnsCloudflare(1, 1, 1, 1);
        WiFi.config(INADDR_NONE, INADDR_NONE, INADDR_NONE, dnsGoogle, dnsCloudflare);
        Serial.printf("[WIFI] DNS Aktif: %s, %s\n", WiFi.dnsIP(0).toString().c_str(), WiFi.dnsIP(1).toString().c_str());

        // Uji DNS resolve ke domain server
        IPAddress serverIP;
        if (WiFi.hostByName("smartos-rw.unique-monsters.my.id", serverIP)) {
            Serial.printf("[DNS SUKSES] Domain server terdeteksi ke IP: %s\n", serverIP.toString().c_str());
        } else {
            Serial.println(F("❌ [DNS GAGAL] Tidak dapat mengubah domain server menjadi IP!"));
            Serial.println(F("   Pastikan Hotspot HP benar-benar terhubung ke internet."));
        }

        if (rc522Ready) beepSuccess();
        // Kirim burst heartbeat pertama kali saat baru menyala
        sendHeartbeat();
    } else {
        Serial.println(F("\n⚠️  [WIFI GAGAL] Tidak dapat terhubung ke WiFi/Hotspot."));
        Serial.println(F("    Pastikan Hotspot HP aktif dan password benar."));
    }

    Serial.println(F("=================================================="));
    if (rc522Ready) {
        Serial.println(F("STATUS: SIAP MEMBACA KARTU e-KTP!"));
        Serial.println(F(">>> Buka web: smartos-rw.unique-monsters.my.id/kartu-rfid"));
        Serial.println(F(">>> Klik tombol 'Buka Sesi Tap Mandiri' di web,"));
        Serial.println(F(">>> lalu dekatkan kartu e-KTP ke sensor RFID."));
    } else {
        Serial.println(F("❌ STATUS: SENSOR RC522 BELUM TERHUBUNG / KABEL KENDOR!"));
        Serial.println(F("   Alat akan terus mencoba mendeteksi modul secara berkala..."));
    }
    Serial.println(F("==================================================\n"));
}

// ============================================================
// 5. LOOP PEMBACAAN KARTU
// ============================================================
void loop() {
    // 1. Cek Koneksi WiFi (Auto-Reconnect yang aman tanpa memicu bentrok driver)
    if (WiFi.status() != WL_CONNECTED) {
        static unsigned long lastReconnectAttempt = 0;
        if (millis() - lastReconnectAttempt > 12000) {
            lastReconnectAttempt = millis();
            Serial.println(F("[WIFI] Sedang mencoba menyambung kembali ke Hotspot..."));
            WiFi.disconnect();
            delay(100);
            WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
        }
    }

    // 1b. Heartbeat berkala ke server (agar web tahu ESP32 ONLINE)
    if (WiFi.status() == WL_CONNECTED && millis() - lastHeartbeatTime >= HEARTBEAT_INTERVAL_MS) {
        lastHeartbeatTime = millis();
        sendHeartbeat();
    }

    // 2. Jika RC522 belum terdeteksi saat boot, coba deteksi ulang setiap 2.5 detik
    if (!rc522Ready) {
        static unsigned long lastRetryTime = 0;
        if (millis() - lastRetryTime > 2500) {
            lastRetryTime = millis();
            Serial.println(F("⏳ [AUTODETECT] Mencoba mendeteksi sensor RC522... Cek kabel jumper!"));
            testRc522Connection();
            if (rc522Ready) {
                Serial.println(F("🎉 [SUKSES] SENSOR RC522 TERDETEKSI! SIAP SCAN KARTU e-KTP!"));
                beepSuccess();
            }
        }
        return;
    }

    // 3. Cek apakah ada kartu e-KTP / RFID di sensor (REQA + WUPA Fallback)
    bool cardPresent = rfid.PICC_IsNewCardPresent();
    if (!cardPresent) {
        // Kartu ISO 14443-4 (e-KTP) yang tertahan di RF field memerlukan perintah WUPA
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
        Serial.println(F("🔍 [SENSOR] Sinyal kartu terdeteksi! Tahan kartu di atas sensor..."));
        delay(15);
        return;
    }

    // 4. Baca nomor seri (UID) kartu
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

    // 5. Kirim UID ke server Smart RW
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
    // Coba fallback pin RST 27 jika pin 22 belum merespon
    if (v == 0x00 || v == 0xFF) {
        rfid = MFRC522(PIN_SS, 27);
        rfid.PCD_Init();
        delay(40);
        v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    }
    // Coba soft-reset (tanpa pin RST fisik)
    if (v == 0x00 || v == 0xFF) {
        rfid = MFRC522(PIN_SS, UINT8_MAX);
        rfid.PCD_Init();
        delay(40);
        v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    }
#endif

    Serial.printf("[RC522] Versi Chip Firmware: 0x%02X\n", v);

    if (v == 0x00 || v == 0xFF) {
        rc522Ready = false;
        Serial.println();
        Serial.println(F("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"));
        Serial.println(F("❌ PERINGATAN KERAS: Modul RC522 TIDAK TERBACA OLEH ESP32 (0x00)!"));
        Serial.println(F("   Penyebab utama kartu tidak bisa terscan:"));
        Serial.println(F("   1. Pin RST sambungkan ke pin GPIO 22 (atau 27)."));
        Serial.println(F("   2. Kaki pin header RC522 BELUM DISOLDER dengan timah panas."));
        Serial.println(F("   3. VCC wajib ke pin 3.3V (JANGAN KE 5V/VIN!)."));
        Serial.println(F("   4. Pin SPI: SDA->GPIO 5, SCK->18, MOSI->23, MISO->19."));
        Serial.println(F("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n"));
        beepError();
    } else {
        rc522Ready = true;
        rfid.PCD_SetAntennaGain(rfid.RxGain_43dB); // Gain 43 dB: stabil dan toleran tanpa saturasi/clipping ADC
        Serial.printf("✅ Modul RC522 BERHASIL TERHUBUNG DENGAN BAIK! (Chip 0x%02X)\n\n", v);
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
    client.setTimeout(10);

    HTTPClient http;
    http.begin(client, API_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + String(DEVICE_KEY));
    http.addHeader("X-Device-Key", String(DEVICE_KEY));
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
// 9. HEARTBEAT KE SERVER (STATUS ONLINE)
// ============================================================
void sendHeartbeat() {
    if (WiFi.status() != WL_CONNECTED) return;

    WiFiClientSecure client;
    client.setInsecure();
    client.setTimeout(6); // 6 detik socket timeout

    HTTPClient http;
    http.begin(client, HEARTBEAT_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Key", String(DEVICE_KEY));
    http.setTimeout(6000); // 6 detik HTTP timeout

    String payload = "{\"device_id\":\"" + String(DEVICE_ID)
                   + "\",\"ip\":\"" + WiFi.localIP().toString() + "\"}";

    int code = http.POST(payload);

    if (code == 200) {
        static unsigned long hbCount = 0;
        if (++hbCount % 5 == 0) {
            Serial.print(F("."));  // Tanda heartbeat online setiap 10 detik
        }
    } else {
        Serial.printf("\n[HEARTBEAT] Gagal (Kode: %d | Info: %s) | Heap: %d B\n", 
            code, 
            http.errorToString(code).c_str(), 
            ESP.getFreeHeap()
        );
        if (code == -1) {
            static unsigned long lastDiagTime = 0;
            if (millis() - lastDiagTime > 15000) {
                lastDiagTime = millis();
                Serial.println(F("💡 [PANDUAN ERROR -1]:"));
                Serial.println(F("   1. Pastikan Hotspot HP benar-benar bisa browsing internet (bukan hanya konek lokal)."));
                Serial.println(F("   2. Pada HP Poco, periksa apakah 'DNS Pribadi (Private DNS)' aktif. Coba matikan sementara."));
                Serial.println(F("   3. Cara paling stabil: Sambungkan ESP32 langsung ke WiFi rumah Anda (bukan lewat Hotspot HP)."));
            }
        }
    }
    http.end();
}

// ============================================================
// 10. NADA BUZZER (OPSIONAL)
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