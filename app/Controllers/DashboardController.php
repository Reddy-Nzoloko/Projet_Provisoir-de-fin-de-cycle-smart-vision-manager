<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Dashboard;

final class DashboardController
{
    public function __construct(private readonly Dashboard $dashboard)
    {
    }

    public function index(): void
    {
        $summary = $this->dashboard->summary();
        $paymentChart = $this->dashboard->paymentChart();
        $trafficChart = $this->dashboard->trafficChart();
        $recentIncidents = $this->dashboard->recentIncidents();

        require __DIR__ . '/../Views/dashboard/index.php';
    }
}
