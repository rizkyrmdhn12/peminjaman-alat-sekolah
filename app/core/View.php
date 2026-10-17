<?php
/** Render view di dalam layout. Nama view/layout hanya berasal dari kode, bukan input pengguna. */
final class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $auth = Auth::user();
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/views/' . $view . '.php';
        $content = ob_get_clean();
        $title = $title ?? APP_NAME;
        require BASE_PATH . '/views/layouts/' . $layout . '.php';
    }
}
