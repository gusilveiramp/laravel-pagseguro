{!! Form::open(['route' => 'pagseguro.send', 'id' => 'pay', 'name' => 'pay']) !!}

<input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
<input type="hidden" id="amount" name="amount" value="{{ $pedido->valor }}">

<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-credit-card"></i> Pagar com Cartão de Crédito
        <div id="brand" class="pull-right">

        </div>
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-sm-8">
                <div class="form-group small text-uppercase">
                    <label>Número</label>
                    <input type="text" id="cardNumber" class="form-control">
                </div>
            </div>
            <div class="col-sm-4 col-xs-6">
                <div class="form-group small text-uppercase">
                    <label><abbr title="Código de Segurança (Atrás do Cartão)">Cód. Seg.</abbr></label>
                    <input type="text" id="cvv" class="form-control">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <div class="form-group small text-uppercase">
                    <label>Mês</label>
                    <select id="expirationMonth" name="expirationMonth"
                            class="form-control">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ (strlen($m) == 1) ? '0' . $m : $m }}">{{ (strlen($m) == 1) ? '0' . $m : $m }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-sm-6 col-xs-6">
                <div class="form-group small text-uppercase">
                    <label>Ano</label>
                    <select id="expirationYear" name="expirationYear" class="form-control">
                        @for($y = 2015; $y <= 2030; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group small text-uppercase">
                    <label>Nome impresso no Cartão</label>
                    <input type="text" id="holderName" name="holderName" class="form-control">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-7">
                <div class="form-group small text-uppercase">
                    <label>CPF do Titular</label>
                    <input type="text" id="holderCpf" name="holderCpf" class="form-control">
                </div>
            </div>
            <div class="col-sm-5">
                <div class="form-group small text-uppercase">
                    <label>Data de Nasc.</label>
                    <input type="text" id="holderBirthDate" name="holderBirthDate" class="form-control">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group small text-uppercase">
                    <label>Parcelas</label>
                    <select id="installments" name="installments"
                            class="form-control">
                        @for($i = 1; $i <= 3; $i++)
                            <option value="{{ $i }}">
                                {{ $i . 'x de R$ ' . price_br($pedido->valor / $i) . ' sem juros' }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p class="text-center">
                    <button id="button" class="btn btn-info btn-block" type="submit"><i
                                class="fa fa-lock"></i> PAGAR
                        COM CARTÃO
                    </button>
                </p>
                <div id="loading" style="display: none" class="text-center">
                    <img src="{{ asset('assets/vendor/pagseguro/images/load-horizontal.gif') }}">
                </div>
            </div>
        </div>
    </div>
</div>

{!! Form::close() !!}