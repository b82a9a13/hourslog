$('#hourslog_form_u')[0].addEventListener('submit', (e)=>{
    e.preventDefault();
    const errorTxt = $('#hl_error_u')[0];
    errorTxt.style.display = 'none';
    let params = '';
    idsArray.forEach((item)=>{
        $(`#td_${item}_u`)[0].style.background = '';
        params += item + '=' + $(`#${item}_u`)[0].value + '&';
    });
    params = params.slice(0, -1);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/update_log_data_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['error']){
                errorTxt.innerText = 'Invalid values: ';
                text['error'].forEach(function(item){
                    if(idsArray.includes(item[0])){
                        $(`#td_${item[0]}_u`)[0].style.background = 'red';
                        errorTxt.innerText += item[1] + '|';
                    }
                });
                errorTxt.style.display = 'block';
            } else {
                if(text['return']){
                    update_delete();
                    update_table();
                    refresh_it();
                    refresh_bar();
                    let success = $('#lt_success')[0];
                    success.innerText = 'Update Success';
                    success.style.display = 'block';
                    success.scrollIntoView();
                } else {
                    errorTxt.innerText = 'Update error.';
                    errorTxt.style.display = 'block';
                }
            }
        } else {
            errorTxt.innerText = 'Connection error.';
            errorTxt.style.display = 'block';
        }
    }
    xhr.send(params);
});