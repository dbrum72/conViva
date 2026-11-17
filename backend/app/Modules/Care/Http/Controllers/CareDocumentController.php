<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CareRecipient;
use App\Modules\Care\Services\CareDocuments;
use Illuminate\Http\Request;

class CareDocumentController extends Controller
{
    private const METADATA_RULES = [
        'category' => 'nullable|string|max:100',
        'valid_until' => 'nullable|date_format:Y-m-d',
        'care_entry_id' => 'nullable|integer|min:1',
    ];

    public function __construct(private CareDocuments $documents) {}

    public function index(Request $request, CareRecipient $recipient)
    {
        $filters = $request->validate([
            'category' => 'nullable|string|max:100',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ]);

        return $this->documents->listing($request->user(), $recipient, $filters);
    }

    public function store(Request $request, CareRecipient $recipient)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,txt,docx,xlsx|max:20480',
            ...self::METADATA_RULES,
        ]);

        return response()->json($this->documents->upload($request->user(), $recipient, $data), 201);
    }

    public function update(Request $request, CareRecipient $recipient, int $document)
    {
        return $this->documents->update(
            $request->user(), $recipient, $document, $request->validate(self::METADATA_RULES)
        );
    }

    public function download(Request $request, CareRecipient $recipient, int $document)
    {
        return $this->documents->download($request->user(), $recipient, $document);
    }

    public function destroy(Request $request, CareRecipient $recipient, int $document)
    {
        $this->documents->delete($request->user(), $recipient, $document);

        return response()->noContent();
    }
}
