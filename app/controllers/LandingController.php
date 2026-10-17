<?php
class LandingController extends Controller
{
    public function index(): void
    {
        $this->render('landing/index', ['title' => APP_NAME . ' - ' . APP_SCHOOL], 'public');
    }
}
