<?php
namespace Farmasi\Service;
use Farmasi\Model\Obat;

class Apotek {
    private array $daftarObat = [];

    public function tambahObat(Obat $obat): void {
        $this->daftarObat[$obat->getKode()] = $obat;
    }

    public function getDaftarObat(): array {
        return $this->daftarObat;
    }

    public function cari(string $kode): ?Obat {
        return $this->daftarObat[$kode] ?? null;
    }
}
