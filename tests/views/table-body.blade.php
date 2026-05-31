@foreach($__partial->getFilteredItems() as $item)
    <tr data-id="{{ $item['id'] }}">
        <td>{{ $item['name'] }}</td>
        <td>${{ number_format($item['price'], 2) }}</td>
        <td>{{ $item['stock'] }}</td>
    </tr>
@endforeach
<tr style="display:none;">
    <td colspan="3" class="table-render-id">{{ uniqid('tbody-') }}</td>
</tr>
