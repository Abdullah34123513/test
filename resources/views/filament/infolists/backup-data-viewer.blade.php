@php
    $data = null;
    try {
        if (is_string($getState())) {
            $data = json_decode($getState(), true);
        } else {
            $data = $getState();
        }
    } catch (\Exception $e) {
    }
@endphp

<div class="overflow-x-auto">
    @if(is_array($data) && count($data) > 0)
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    @foreach(array_keys($data[0]) as $key)
                        <th scope="col" class="px-6 py-3">
                            {{ ucfirst($key) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        @foreach($row as $cell)
                            <td class="px-6 py-4">
                                {{ is_array($cell) ? json_encode($cell) : $cell }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="p-4 text-gray-500">
            No data available or invalid format.
            <pre class="mt-2 text-xs">{{ json_encode($getState(), JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>
