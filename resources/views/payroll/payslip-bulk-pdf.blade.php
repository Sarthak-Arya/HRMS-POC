<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Payslips</title><style>.page-break { page-break-after: always; }</style></head>
<body>
@foreach($pages as $index => $page)
    @include('payroll.payslip-pdf', $page)
    @if($index < count($pages) - 1)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
