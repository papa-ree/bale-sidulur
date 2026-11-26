<?php

namespace Bale\BaleSidulur\Livewire\PublicPage;

use Bale\BaleSidulur\Services\DocumentLink;
use Bale\BaleSidulur\Services\TicketTracker;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('bale-sidulur::layouts.guest')]
#[Title('Cek Tiket — Sidulur')]
class CheckTicket extends Component
{
    public string $ticket = '';

    public string $nik = '';

    public bool $verified = false;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    /** @var array<int, array{name: string, url: string}> */
    public array $documents = [];

    public function updatedTicket(): void
    {
        $this->resetResult();
    }

    public function check(TicketTracker $tracker, DocumentLink $links): void
    {
        $this->validate([
            'ticket' => ['required', 'string', 'max:32'],
            'nik' => ['required', 'digits:16'],
        ]);

        try {
            $pendaftaran = $tracker->find($this->ticket, $this->nik);
        } catch (Throwable $e) {
            report($e);
            $this->addError('ticket', 'Layanan sedang tidak dapat diakses. Silakan coba lagi nanti.');

            return;
        }

        $this->nik = '';

        if (! $pendaftaran) {
            $this->addError('ticket', 'Data tidak ditemukan. Periksa kembali nomor tiket dan NIK.');

            return;
        }

        $this->ticket = $pendaftaran->ticket_number;
        $this->verified = true;
        $this->result = [
            'ticket' => $pendaftaran->ticket_number,
            'service' => $pendaftaran->service?->nama,
            'bidang' => $pendaftaran->service?->bidang?->nama,
            'status' => $pendaftaran->publicStatus(),
            'created_at' => $pendaftaran->created_at?->format('d M Y H:i'),
            'scheduled_at' => $pendaftaran->pelayananBidang?->scheduled_at?->format('d M Y'),
            'timeline' => $pendaftaran->timelines->map(fn ($line) => [
                'stage' => str($line->stage)->title(),
                'status' => str($line->to_status)->replace('_', ' ')->title(),
                'note' => $line->note,
                'time' => $line->created_at?->format('d M Y H:i'),
            ])->all(),
        ];

        $this->documents = $pendaftaran->pelayananBidang?->status !== 'selesai'
            ? []
            : $pendaftaran->documents
                ->filter(fn ($document) => $document->isPublic())
                ->map(fn ($document) => [
                    'name' => strtoupper($document->kind).' — '.($document->original_name ?: 'Dokumen'),
                    'url' => $links->for($document),
                ])
                ->filter(fn ($document) => filled($document['url']))
                ->values()
                ->all();
    }

    public function resetResult(): void
    {
        $this->verified = false;
        $this->result = null;
        $this->documents = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('bale-sidulur::livewire.public-page.check-ticket');
    }
}
