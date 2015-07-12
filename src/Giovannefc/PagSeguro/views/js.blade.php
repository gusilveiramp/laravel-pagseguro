<script type="text/javascript">

<!-- Funções para executar a Rota de Pagamento -->

function confirmBoleto() {
    $("#confirmBoleto").attr("disabled", "disabled");
    document.getElementById("loadPagamento").style.display = "block";
    senderHash = PagSeguroDirectPayment.getSenderHash();
    $.post("{{ route('PagSeguroAjaxSenderHash') }}", {
        _token: "{{ csrf_token() }}",
        data: (senderHash)
    });
    setTimeout(function() {
        window.location.href = "{{ route(PagSeguro::viewSendRoute(), 'boleto') }}";
    }, 2500);
}

function confirmCartao() {
    var parametros = {

        cardNumber: $("#cardNumber").val(),
        brand: (brand),
        cvv: $("#cvv").val(),
        expirationMonth: $("#expirationMonth :selected").val(),
        expirationYear: $("#expirationYear :selected").val(),
        success: function(data) {
            $.post("{{ route('PagSeguroAjaxCreditCardToken') }}", {
                _token: "{{ csrf_token() }}",
                data: (JSON.stringify(data.card.token).replace(/"/g, ''))
            });
        }
    }

    $("#confirmCartao").attr("disabled", "disabled");
    document.getElementById("loadPagamento").style.display = "block";

    setSenderHash();
    setInfoHolder();
    PagSeguroDirectPayment.createCardToken(parametros);

    setTimeout(function() {
        window.location.href = "{{ route(PagSeguro::viewSendRoute(), 'credit_card') }}";
    }, 2500);
}

function setSenderHash() {
    senderHash = PagSeguroDirectPayment.getSenderHash();
    setTimeout(function() {
        $.post("{{ route('PagSeguroAjaxSenderHash') }}", {
            _token: "{{ csrf_token() }}",
            data: (senderHash)
        });
    }, 1000);
}

function setInfoHolder() {
    $.post("{{ route('PagSeguroAjaxInfoHolder') }}", {
        _token: "{{ csrf_token() }}",
        holderName: $("#holderName").val(),
        holderCpf: $("#holderCpf").val(),
        holderBirthDate: $("#holderBirthDate").val()
    });
}

window.onload = function() {

    $("#boleto").hide();

    $('a#boletoBtn').click(function() {
        $("#cartao").hide();
        $("#boleto").fadeIn(500);
        $("#boletoNav").addClass('active');
        $("#cartaoNav").removeClass('active');
        return false;
    })

    $('a#cartaoBtn').click(function() {
        $("#boleto").hide();
        $("#cartao").fadeIn(500);
        $("#cartaoNav").addClass('active');
        $("#boletoNav").removeClass('active');

        return false;
    })

    {!! PagSeguro::jsSetSessionId() !!}

    $("#cardNumber").blur(function() {
        var cardNumber = document.getElementById("cardNumber").value;
        PagSeguroDirectPayment.getBrand({
            cardBin: cardNumber.replace(/ /g, ''),
            success: function(data) {
                brand = JSON.stringify(data.brand.name).replace(/"/g, '');
                $("#brand").fadeIn(600);
                $("#brandName").html(brand);
            }
        });
    });

    $('#formCartao').formValidation({
        framework: 'bootstrap',
        icon: {
            valid: '',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            cardNumber: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    }
                }
            },
            expirationMonth: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    }
                }
            },
            expirationYear: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    }
                }
            },
            cvv: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    }
                }
            },
            holderName: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    }
                }
            },
            holderBirthDate: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    },
                    date: {
                        format: 'DD/MM/YYYY',
                        message: 'Preenchimento incompleto.'
                    }
                }
            },
            holderCpf: {
                trigger: 'blur',
                validators: {
                    notEmpty: {
                        message: 'Campo obrigatório.'
                    },
                    id: {
                        country: 'BR',
                        message: 'Por favor, digite um CPF válido.'
                    }
                }
            },

        }
    }).on('success.form.fv', function(e) {
        e.preventDefault();

        var $form = $(e.target),
            fv = $(e.target).data('formValidation');

        confirmCartao();

    });
}
</script>
