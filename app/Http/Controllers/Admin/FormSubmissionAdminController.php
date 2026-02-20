<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormSubmissionAdminController extends Controller
{
    public function index(Request $request, Form $form): View
    {
        $submissions = FormSubmission::query()
            ->where('form_id', $form->id)
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.forms.submissions.index', [
            'form' => $form,
            'submissions' => $submissions,
        ]);
    }

    public function show(Form $form, FormSubmission $submission): View
    {
        abort_unless($submission->form_id === $form->id, 404);

        return view('admin.forms.submissions.show', [
            'form' => $form,
            'submission' => $submission,
        ]);
    }
}
