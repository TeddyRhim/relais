<div>
    <div class="flex justify-between text-sm text-ink-600">
        <span>{{ number_format($used, 0, ',', ' ') }} sur {{ number_format($limit, 0, ',', ' ') }} événements ce mois-ci</span>
        <span class="font-mono text-ink">{{ $percent }} %</span>
    </div>
    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-ink/10" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-brick' : ($percent >= 80 ? 'bg-amber' : 'bg-gradient-to-r from-ember-400 to-ember-600') }}" style="width: {{ max($percent, $used > 0 ? 2 : 0) }}%"></div>
    </div>
    @if ($percent >= 100)
        <p class="mt-2 text-sm text-brick">Quota atteint : les nouveaux webhooks sont refusés jusqu'au mois prochain ou au changement de plan.</p>
    @endif
</div>
