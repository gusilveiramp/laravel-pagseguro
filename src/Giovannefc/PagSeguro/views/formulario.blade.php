<div class="panel panel-info">
    <div class="panel-heading">
        Escolha a forma de Pagamento
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4" style="padding-right: 0px;">
                <ul class="nav nav-pills nav-stacked check-forma-nav">
                    <li id="cartaoNav" role="presentation" class="active"><a id="cartaoBtn" href="#"><i class="fa fa-credit-card fa-2x pull-right"></i> Cartão de Crédito</a></li>
                    <li id="boletoNav" role="presentation"><a id="boletoBtn" href="#"><i class="fa fa-barcode fa-2x pull-right"></i> Boleto</a></li>
                </ul>
                <img class="img-responsive center-block check-selos" src="{{ asset('vendor/pagseguro/images/amb_seguro.png') }}">
                <img class="img-responsive center-block check-selos" src="{{ asset('vendor/pagseguro/images/dir_pagseguro.png') }}">
            </div>
            <div class="col-xs-12 col-sm-8 col-md-8 col-lg-8" style="padding-left: 0px;">
                <div class="check-form" id="boleto">
                    <h4 align="center"><i class="fa fa-arrow-circle-right"></i> Pagar com Boleto</h4>
                    <div class="text-center check-btn-boleto">
                        <button id="confirmBoleto" onclick="confirmBoleto()" class="btn btn-success btn-lg"><i class="fa fa-lock"></i> Finalizar Compra</button>
                    </div>
                    <div class="alert alert-info">
                        <strong><i class="fa fa-info-circle"></i> Aviso!</strong>
                        <br> Pagamento em boleto leva de 1 a 2 dias úteis para compensar no banco. O prazo de entrega é contado a partir da <strong>confirmação	do pagamento.</strong>
                    </div>
                </div>
                <div class="check-form" id="cartao">
                {!! Form::open(['id' => 'formCartao', 'class' => 'form-horizontal']) !!}
                    <div class="form-group">
                        {!! Form::label('cardNumber', 'Número do Cartão', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-7">
                            {!! Form::text('cardNumber', null, array('class' => 'form-control')) !!}
                        </div>
                    </div>
                    <div style="display: none" id="brand" class="form-group">
                        {!! Form::label('brand', 'Bandeira', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-7">
                            <p id="brandName" class="form-control-static" style="text-transform: capitalize"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('expirationMonth', 'Validade (Mês/Ano)', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-3" style="display: inline-block">
                            {!! Form::select('expirationMonth', PagSeguro::viewMesesAnos()['meses'], null, ['id' => 'expirationMonth', 'class' => 'form-control']) !!}
                        </div>
                        <div class="col-sm-3" style="display: inline-block">
                            {!! Form::select('expirationYear', PagSeguro::viewMesesAnos()['anos'], null, ['id' => 'expirationYear', 'class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('cvv', 'Codigo de Segurança', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-3">
                            {!! Form::text('cvv', null, array('class' => 'form-control')) !!}
                        </div>
                    </div>
                    <h5><i class="fa fa-arrow-circle-right"></i> Dados do Titular do Cartão</h5>
                    <div class="form-group">
                        {!! Form::label('holderName', 'Nome', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-7">
                            {!! Form::text('holderName', null, array('class' => 'form-control', 'placeholder' => 'Igual no Cartão')) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('holderBirthDate', 'Data de Nascimento', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-5">
                            {!! Form::text('holderBirthDate', null, array('class' => 'form-control', 'placeholder' => 'DD/MM/AAAA')) !!}
                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::label('holderCpf', 'CPF', array('class' => 'col-sm-5 control-label')) !!}
                        <div class="col-sm-5">
                            {!! Form::text('holderCpf', null, array('class' => 'form-control')) !!}
                        </div>
                    </div>
                    <div class="text-center">
                        <button id="confirmCartao" type="submit" class="btn btn-success btn-lg"><i class="fa fa-lock"></i> Finalizar Compra</button>
                    </div>
                    {!! Form::close() !!}
                    
                </div>
                <div id="loadPagamento" style="display: none" class="text-center">
                    <img src="{{ asset('vendor/pagseguro/images/load-horizontal.gif') }}">
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">

.check-selos {
    margin-top: 10px;
    margin-bottom: 10px;
}

.check-forma-nav {
    margin-top: 30px;
}

.check-forma-nav > li > a {
    padding: 18px 10px 18px 10px;
}

.check-forma-nav > li > a {
    border: 1px solid #ddd;
}

.check-forma-nav > li.active > a,
.check-forma-nav > li.active > a:hover,
.check-forma-nav > li.active > a:focus {
    background-color: #ddd;
    border-radius: 4px 0 0 4px;
    color: #777777;
}

.check-form {
    padding: 5px;
    background-color: #ddd;
    border-radius: 4px;
}

.check-btn-boleto {
    margin-top: 15px;
    margin-bottom: 20px;
}

</style>

<script src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>