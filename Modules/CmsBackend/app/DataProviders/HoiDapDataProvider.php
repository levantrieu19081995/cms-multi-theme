<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsFrontend\Models\Question;

class HoiDapDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new Question());
    }
    public function getAll()
    {
        return Question::with(['user', 'admin'])->latest()->get();
    }

    public function getApproved()
    {
        // return Question::where('is_approved', true)->whereNotNull('answered_at')->latest()->get();
        return Question::where('is_approved', true)->latest()->get();
    }

    public function store($data)
    {
        return Question::create($data);
    }
    public function findOrFail($id)
    {
        return Question::findOrFail($id);
    }

    public function answerQuestion($id, $answer, $adminId)
    {
        return Question::where('id', $id)->update([
            'answer' => $answer,
            'answered_by' => $adminId,
            'answered_at' => now(),
            'is_approved' => true,
        ]);
    }

     public function approveQuestion($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return false; // hoặc throw Exception
        }

        $question->is_approved = !$question->is_approved;
        return $question->save();
    }
}