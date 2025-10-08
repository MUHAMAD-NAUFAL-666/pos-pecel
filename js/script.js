$(document).ready(function(){
    $('.menu-card').click(function(){
        let index = $('.menu-card').index(this);
        let price = parseInt($(this).data('price'));
        let qtyInput = $('input[name="qty[]"]').eq(index);
        let qty = parseInt(qtyInput.val());
        qty += 1;
        qtyInput.val(qty);
        $(this).addClass('selected');

        // Hitung total
        let total = 0;
        $('input[name="qty[]"]').each(function(i){
            let q = parseInt($(this).val());
            let p = parseInt($('.menu-card').eq(i).data('price'));
            total += q * p;
        });
        $('#total').text(total.toLocaleString('id-ID'));
    });
});
