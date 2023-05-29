<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Dashboard\Http\Requests\DashboardRequest;
use Modules\Signal\Entities\Signal;
use Modules\Signal\Repositories\SignalRepository;

class PageController extends Controller
{
    private array $periods = [
        'All time',
        'Year',
        'Month',
        'Week'
    ];

    public function __construct(private SignalRepository $signalRepository)
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(DashboardRequest $request)
    {
        $stats = $this->signalRepository->getStats($request->period ?? 0);

        return view('dashboard::index', [
            'periods' => $this->periods,
            'stats' => $stats
        ]);
    }
}
