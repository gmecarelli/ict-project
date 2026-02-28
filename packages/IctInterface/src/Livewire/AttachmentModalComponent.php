<?php

/**
 * AttachmentModalComponent
 *
 * Componente Livewire per gestione allegati via modale Bootstrap 5.
 * Permette upload, listing e eliminazione di allegati per qualsiasi entita'.
 *
 * Uso: @livewire('ict-attachment-modal')
 *
 * Apertura da pulsante:
 *   Livewire.dispatch('open-attachment-modal', {
 *       attachableType: 'App\\Models\\Book',
 *       attachableId: 123
 *   })
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Packages\IctInterface\Models\Attachment;
use Packages\IctInterface\Services\AttachmentService;

class AttachmentModalComponent extends Component
{
    use WithFileUploads;

    public bool $showModal = false;
    public ?string $attachableType = null;
    public ?int $attachableId = null;
    public $file = null;
    public string $description = '';
    public array $attachments = [];

    protected $listeners = ['open-attachment-modal' => 'openModal'];

    public function openModal(string $attachableType, int $attachableId): void
    {
        $this->attachableType = $attachableType;
        $this->attachableId = $attachableId;
        $this->showModal = true;
        $this->loadAttachments();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['file', 'description']);
        $this->resetValidation();
    }

    public function loadAttachments(): void
    {
        $this->attachments = Attachment::where('attachable_type', $this->attachableType)
            ->where('attachable_id', $this->attachableId)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function upload(): void
    {
        $maxSize = config('ict.upload_max_size', 10240);
        $this->validate([
            'file' => "required|file|max:{$maxSize}",
        ]);

        $service = app(AttachmentService::class);
        $service->store(
            $this->file,
            $this->attachableType,
            $this->attachableId,
            $this->description ?: null,
            Str::snake(class_basename($this->attachableType))
        );

        $this->reset(['file', 'description']);
        $this->loadAttachments();
        $this->dispatch('attachment-saved');

        session()->flash('attach_message', 'Allegato caricato con successo');
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $service = app(AttachmentService::class);
        $service->delete($attachment);

        $this->loadAttachments();
        $this->dispatch('attachment-deleted');

        session()->flash('attach_message', 'Allegato eliminato');
    }

    public function render()
    {
        return view('ict::livewire.attachment-modal');
    }
}
