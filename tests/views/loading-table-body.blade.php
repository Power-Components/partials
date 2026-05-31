<div id="loading-table-content">
    <span id="table-body-uniqid" style="display:none;">{{ uniqid('table-') }}</span>

    @if($__partial->isLoading)
        <div id="loading-indicator" class="loading">Loading...</div>
    @endif

    <table>
        <tbody>
            @foreach($__partial->items as $item)
                <tr data-id="{{ $item['id'] }}">
                    <td>{{ $item['name'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
