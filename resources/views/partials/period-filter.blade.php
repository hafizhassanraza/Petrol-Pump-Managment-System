{{-- Requires: $filter, $from, $to, optional $formAction (defaults current URL) --}}
<div class="filter-section">
    <h5><i class="bi bi-funnel"></i> Filter</h5>
    <form method="GET" action="{{ $formAction ?? request()->url() }}" id="periodFilterForm">
        <div class="filter-options">
            <button type="button" class="filter-btn @if(($filter ?? 'today') === 'today') active @endif" onclick="setPeriodFilter('today')">
                <i class="bi bi-calendar-check"></i> Today
            </button>
            <button type="button" class="filter-btn @if(($filter ?? '') === 'last-week') active @endif" onclick="setPeriodFilter('last-week')">
                <i class="bi bi-calendar-week"></i> Last 7 Days
            </button>
            <button type="button" class="filter-btn @if(($filter ?? '') === 'last-month') active @endif" onclick="setPeriodFilter('last-month')">
                <i class="bi bi-calendar-month"></i> Last 30 Days
            </button>
            <button type="button" class="filter-btn @if(($filter ?? '') === 'custom') active @endif" onclick="setPeriodFilter('custom')">
                <i class="bi bi-calendar-range"></i> Custom
            </button>
        </div>
        <div class="date-range-group" id="customDateRange" style="display: @if(($filter ?? '') === 'custom') flex @else none @endif;">
            <div class="date-input-group">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $from ?? '' }}" class="form-control">
            </div>
            <div class="date-input-group">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $to ?? '' }}" class="form-control">
            </div>
            <button type="submit" class="btn-filter"><i class="bi bi-search"></i> Apply</button>
        </div>
        <input type="hidden" id="filterInput" name="filter" value="{{ $filter ?? 'today' }}">
    </form>
</div>
@once
@push('scripts')
<script>
function setPeriodFilter(type) {
    document.getElementById('filterInput').value = type;
    if (type === 'custom') {
        document.getElementById('customDateRange').style.display = 'flex';
    } else {
        document.getElementById('customDateRange').style.display = 'none';
        document.getElementById('periodFilterForm').submit();
    }
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
}
</script>
@endpush
@endonce
