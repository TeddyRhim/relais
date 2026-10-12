@if ($events->isEmpty())
    <x-empty-state title="Aucun événement reçu" text="Les webhooks envoyés à l'adresse d'une source apparaissent ici, avec leur contenu et leur signature." />
@else
    <div class="mt-4 overflow-x-auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Reçu</th>
                    <th>Source</th>
                    <th>Signature</th>
                    <th>Taille</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td class="font-mono text-xs">{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $event->source->name }}</td>
                        <td>@include('events._signature', ['status' => $event->signature_status])</td>
                        <td class="font-mono text-xs text-ink-500">{{ strlen($event->body) }} o</td>
                        <td class="text-right"><a href="{{ route('events.show', $event) }}" class="link">Détail</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
