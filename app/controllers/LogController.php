<?php
class LogController extends Controller
{
    public const AKSI = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'BORROW', 'RETURN', 'DAMAGE', 'LOST', 'FINE'];

    public function index(): void
    {
        $lm = new LogModel();
        $moduls = $lm->moduls();
        $f = [
            'q' => mb_substr($this->get('q'), 0, 50),
            'modul' => $this->enumGet('modul', $moduls),
            'aksi' => $this->enumGet('aksi', self::AKSI),
            'dari' => $this->dateGet('dari'),
            'sampai' => $this->dateGet('sampai'),
        ];
        $this->render('log/index', ['title' => 'Log Aktivitas', 'f' => $f, 'moduls' => $moduls, 'aksi' => self::AKSI, 'pg' => $lm->paginate($f, $this->page())]);
    }
}
