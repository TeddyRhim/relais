<div>
    <div class="flex justify-between text-sm text-gray-700">
        <span>{{ number_format($used, 0, ',', ' ') }} sur {{ number_format($limit, 0, ',', ' ') }} événements ce mois-ci</span>
        <span>{{ $percent }} %</span>
    </div>
    <div class="mt-2 h-2 bg-gray-200 rounded" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-2 rounded {{ $percent >= 100 ? 'bg-red-600' : ($percent >= 80 ? 'bg-yellow-500' : 'bg-indigo-600') }}" style="width: {{ $percent }}%"></div>
    </div>
    @if ($percent >= 100)
        <p class="mt-2 text-sm text-red-700">Quota atteint : les nouveaux webhooks sont refusés jusqu'au mois prochain ou au changement de plan.</p>
    @endif
</div>
