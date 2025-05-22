<div>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($directors as $index => $director)
                <tr>
                    <td>{{ $director['name'] }}</td>
                    <td>{{ $director['email'] }}</td>
                    <td>{{ $director['phone'] }}</td>
                    <td>
                        <button wire:click="removeDirector({{ $index }})" class="btn btn-sm btn-danger">Remove</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>