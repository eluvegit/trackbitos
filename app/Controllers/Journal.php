<?php

namespace App\Controllers;

use App\Models\TaskLogModel;

class Journal extends BaseController
{
    protected TaskLogModel $taskLogModel;

    public function __construct()
    {
        $this->taskLogModel = new TaskLogModel();
    }

    public function index()
    {
        $viewMode = $this->request->getGet('view') ?? 'portadas';

        $taskLogs = $this->taskLogModel->getAll();

        return view('journal/index', [
            'view_mode' => $viewMode,
            'task_logs' => $taskLogs
        ]);
    }

    public function view(int $logId)
    {
        $log = $this->taskLogModel->getById($logId);

        if (!$log) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('journal/view', [
            'log' => $log
        ]);
    }

    public function edit(int $logId)
    {
        $log = $this->taskLogModel->getById($logId);

        if (!$log) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'post') {

            $data = [
                'date'       => $this->request->getPost('date'),
                'time_spent' => $this->request->getPost('time_spent'),
                'progress'   => $this->request->getPost('progress'),
                'note'       => $this->request->getPost('note'),
            ];

            $this->taskLogModel->update($logId, $data);

            return redirect()->to('/journal');
        }

        return view('journal/edit', [
            'log' => $log
        ]);
    }
}
