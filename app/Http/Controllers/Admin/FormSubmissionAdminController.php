<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionAdminController extends Controller
{
    public function index(Request $request, Form $form)
    {
        $status = (string) $request->query('status', '');

        $q = FormSubmission::query()
            ->where('form_id', $form->id)
            ->orderByDesc('created_at');

        if ($status !== '') {
            $q->where('mail_status', $status);
        }

        $submissions = $q->paginate(25)->withQueryString();

        $stats = FormSubmission::query()
            ->where('form_id', $form->id)
            ->selectRaw("mail_status, COUNT(*) as c")
            ->groupBy('mail_status')
            ->pluck('c', 'mail_status')
            ->toArray();

        return view('admin.forms.submissions.index', [
            'form' => $form,
            'submissions' => $submissions,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    public function show(Form $form, FormSubmission $submission)
    {
        abort_unless($submission->form_id === $form->id, 404);

        return view('admin.forms.submissions.show', [
            'form' => $form,
            'submission' => $submission,
        ]);
    }

    public function exportCsv(Request $request, Form $form): StreamedResponse
    {
        $filename = 'form_' . $form->slug . '_submissions_' . now()->format('Ymd_His') . '.csv';

        $q = FormSubmission::query()
            ->where('form_id', $form->id)
            ->orderByDesc('created_at');

        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $q->where('mail_status', $status);
        }

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');

            // Determine headers based on union of payload keys (first 200 rows)
            $rows = $q->limit(200)->get(['payload']);
            $keys = [];
            foreach ($rows as $r) {
                if (!is_array($r->payload)) continue;
                foreach (array_keys($r->payload) as $k) {
                    $keys[$k] = true;
                }
            }
            $payloadKeys = array_values(array_keys($keys));

            $header = array_merge([
                'id',
                'created_at',
                'ip',
                'mail_status',
                'to_email',
                'mail_sent_at',
                'spam_reason',
            ], $payloadKeys);

            fputcsv($out, $header);

            $q->chunk(250, function ($chunk) use ($out, $payloadKeys) {
                foreach ($chunk as $s) {
                    $row = [
                        $s->id,
                        optional($s->created_at)->toDateTimeString(),
                        $s->ip,
                        $s->mail_status,
                        $s->to_email,
                        optional($s->mail_sent_at)->toDateTimeString(),
                        $s->spam_reason,
                    ];

                    $payload = is_array($s->payload) ? $s->payload : [];
                    foreach ($payloadKeys as $k) {
                        $v = $payload[$k] ?? '';
                        if (is_array($v)) $v = json_encode($v);
                        $row[] = $v;
                    }

                    fputcsv($out, $row);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
