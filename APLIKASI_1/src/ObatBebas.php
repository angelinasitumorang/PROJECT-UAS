<?php
namespace Farmasi\Model;

class ObatBebas extends Obat {
    public function getJenis(): string {
        return "Obat Bebas";
    }

    public function perluResep(): bool {
        return false;
    }
}
