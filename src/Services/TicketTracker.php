<?php

namespace Bale\BaleSidulur\Services;

use Bale\BaleSidulur\Models\Pendaftaran;
use Bale\BaleSidulur\Support\BlindIndex;

class TicketTracker
{
    /**
     * Cari pendaftaran berdasarkan tiket + NIK. Null untuk semua kegagalan
     * (tidak ada, kedaluwarsa, NIK salah) agar tidak membocorkan keberadaan tiket.
     */
    public function find(string $ticket, string $nik): ?Pendaftaran
    {
        $pendaftaran = Pendaftaran::query()
            ->with(['citizen', 'service.bidang', 'pelayananBidang', 'timelines'])
            ->where('ticket_number', strtoupper(trim($ticket)))
            ->first();

        if (! $pendaftaran || $pendaftaran->isExpired()) {
            return null;
        }

        $hash = BlindIndex::make($nik);

        if (! $hash || ! hash_equals((string) $pendaftaran->citizen?->nik_hash, $hash)) {
            return null;
        }

        return $pendaftaran;
    }
}
