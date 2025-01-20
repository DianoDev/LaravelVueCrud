@if (session()->has('success') || session()->has('error'))
    @if (session()->has('success'))
        <notification message="{{session()->get('success')}}" type="success" show="true"></notification>
    @else
        <notification message="{{session()->get('error')}}" type="error" show="true"></notification>
    @endif
@else
    <notification></notification>
@endif
