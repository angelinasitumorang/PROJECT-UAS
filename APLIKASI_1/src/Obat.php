<?php
namespace Farmasi\Model;

class Obat {
    private string $kode;
    private string $nama;
    private int $stok;
    private float $harga;

    public function __construct(string $kode, string $nama, int $stok, float $harga) {
        $this->kode = $kode;
        $this->setNama($nama);
        $this->setStok($stok);
        $this->setHarga($harga);
    }

    // Getter
    public function getKode(): string  { return $this->kode; }
    public function getNama(): string  { return $this->nama; }
    public function getStok(): int     { return $this->stok; }
    public function getHarga(): float  { return $this->harga; }

    // Setter dengan validasi (inti encapsulation)
    public function setNama(string $nama): void {
        if (trim($nama) === '') {
            throw new \InvalidArgumentException("Nama obat tidak boleh kosong");
        }
        $this->nama = $nama;
    }

    public function setStok(int $stok): void {
        if ($stok < 0) {
            throw new \InvalidArgumentException("Stok tidak boleh negatif");
        }
        $this->stok = $stok;
    }

    public function setHarga(float $harga): void {
        if ($harga <= 0) {
            throw new \InvalidArgumentException("Harga harus lebih dari 0");
        }
        $this->harga = $harga;
    }

    public function kurangiStok(int $jumlah): bool {
        if ($jumlah > $this->stok) {
            return false;
        }
        $this->stok -= $jumlah;
        return true;
    }

    // Method yang akan di-override oleh child class
    public function getJenis(): string {
        return "Obat Umum";
    }

    public function perluResep(): bool {
        return false;
    }

    // Status stok: dipakai untuk pengingat stok di halaman
    public function getStatusStok(): string {
        if ($this->stok <= 50)  return "Hampir Habis";
        if ($this->stok <= 100) return "Menipis";
        return "Aman";
    }

    public function getInfo(): string {
        return "[{$this->kode}] {$this->nama} | Jenis: {$this->getJenis()} | Stok: {$this->stok} | Harga: Rp "
            . number_format($this->harga, 0, ',', '.');
    }
}
