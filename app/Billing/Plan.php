<?php

namespace App\Billing;

enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';

    public function label(): string
    {
        return config("relais.plans.{$this->value}.label");
    }

    public function price(): string
    {
        return config("relais.plans.{$this->value}.price");
    }

    public function monthlyEventLimit(): int
    {
        return (int) config("relais.plans.{$this->value}.events_per_month");
    }
}
