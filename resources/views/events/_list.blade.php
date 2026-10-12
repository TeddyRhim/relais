@if ($events->isEmpty())
    <p class="mt-4 text-gray-600">Aucun événement reçu.</p>
@else
    <table class="mt-4 w-full text-sm text-left">
        <thead class="text-gray-500">
            <tr>
                <th class="py-2">Reçu</th>
                <th>Source</th>
                <th>Signature</th>
                <th>Taille</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach ($events as $event)
                <tr>
                    <td class="py-2">{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $event->source->name }}</td>
                    <td>{{ ['valid' => 'valide', 'invalid' => 'invalide', 'missing' => 'absente'][$event->signature_status] }}</td>
                    <td>{{ strlen($event->body) }} o</td>
                    <td class="text-right"><a href="{{ route('events.show', $event) }}" class="text-indigo-700 underline">Détail</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
