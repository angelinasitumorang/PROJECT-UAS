<?php
namespace Farmasi\Model;

/**
 * INHERITANCE: ObatLain mewarisi class Obat
 * Untuk vitamin, cairan infus, antiseptik, obat luar, dan perlengkapan medis
 */
class ObatLain extends Obat {
    private string $kategori;

    public function __construct(string $kode, string $nama, int $stok, float $harga, string $kategori) {
        parent::__construct($kode, $nama, $stok, $harga);
        $this->kategori = $kategori;
    }

    public function getKategori(): string { return $this->kategori; }

    public function getJenis(): string {
        return $this->kategori;
    }

    public function perluResep(): bool {
        return false;
    }
}
