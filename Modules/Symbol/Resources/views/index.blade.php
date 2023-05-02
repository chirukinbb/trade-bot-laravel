@extends('adminlte::page')

@section('title','Symbols')

@section('content_header')
    <h1>Symbols</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Symbol Table</h3>
            <div class="card-tools">
                <div class="input-group">
                    <input type="search" class="form-control form-control-lg" placeholder="Search by Symbol">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-lg btn-default">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Enable</th>
                    <th style="width: 10px">Symbol</th>
                    @foreach(config('symbol.exchanges') as $exchange)
                        <th>{{$exchange['title']}}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr class="loader">
                    <td colspan="10" class="p-5 text-center text-black-50">
                        <i class="spinner-border"></i>
                    </td>
                </tr>
                </tbody>
            </table>
            <template id="row">
                <tr style="cursor: pointer" data-symbol="">
                    <td><input type="checkbox" value="1"></td>
                    <td class="symbol"></td>
                    @foreach(config('symbol.exchanges') as $key => $exchange)
                        <td class="{{$key}}"></td>
                    @endforeach
                </tr>
            </template>
        </div>
    </div>
@endsection

@section('js')
    <script>
        let raw = $('#row').html(),
            count = 50,
            symbols,
            symDubl,
            row,
            start

        $.ajax({
            url:'{{route('symbol::symbols')}}',
            headers:{Authorization:'Bearer {{$token}}'},
            success:function (res) {
                symbols = Object.values(res)
                symDubl = Object.values(res)

                render(0)

                $('tr.loader').hide()
            }
        })

        $('body').on('click','tr',function (e) {
            e.preventDefault()

            let data = {symbol:$(this).data('symbol')},
                url = null

            if($(this).find('input[type=checkbox]')[0].checked){
                $(this).find('input[type=checkbox]').attr('checked',false)
                url = '{{route('symbol::delete')}}'
            }else {
                $(this).find('input[type=checkbox]').attr('checked',true)
                url = '{{route('symbol::store')}}'
            }

            $.ajax({url,data,method:'post',headers:{Authorization:'Bearer {{$token}}'}})
        })

        $('input[type=search]').on('keyup click',function () {
            let search = $(this).val().toUpperCase()

            symDubl = symbols.filter(function (symbol) {
                return symbol.hasOwnProperty('label') && symbol.label.includes(search)
            })

            render(0,true)
        })

        function render(n,clear) {
            start = n

            if (clear)
            $('tbody').html('')

            $(symDubl.slice(n,n+count)).each(function (i,tr) {
                row = $(raw).clone()

                row.attr('data-symbol',tr.label)
                row.find('.symbol').text(tr.label)
                row.find('input[type=checkbox]').attr('checked',tr.enabled)

                $.each(tr.exchanges,function (exchange,value) {
                    row.find('.'+exchange).addClass(value ? 'bg-success' : 'bg-danger')
                    row.find('.'+exchange).text(value ? 'yes' : 'no')
                })

                $('tbody').append(row)
            })
        }

        // Добавляем обработчик события scroll на окно
        window.addEventListener("scroll", function() {

            // Получаем высоту документа и высоту окна
            var docHeight = document.documentElement.scrollHeight;
            var winHeight = window.innerHeight;

            // Вычисляем пороговое значение прокрутки
            var threshold = docHeight * 0.8;
            // Получаем координату верхней и нижней границы окна относительно документа
            var scrollTop = window.scrollY;
            var scrollBottom = scrollTop + winHeight;

            // Проверяем, превышает ли координата верхней или нижней границы окна пороговое значение прокрутки
            if (scrollBottom >= threshold) {
                render(start+count,false)
            }
        });
    </script>
@endsection
