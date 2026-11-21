<?php
namespace Farmasi\Model;

class ObatKeras extends Obat {
    private string $golongan;

    public function __construct(string $kode, string $nama, int $stok, float $harga, string $golongan) {
        parent::__construct($kode, $nama, $stok, $harga);
        $this->golongan = $golongan;
    }

    public function getGolongan(): string { return $this->golongan; }

    public function getJenis(): string {
        return "Obat Keras (Gol. {$this->golongan})";
    }

    public function perluResep(): bool {
        return true;
    }
}
