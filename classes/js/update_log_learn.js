document.getElementById('hourslog_form_u').addEventListener('submit', (e)=>{
    e.preventDefault();
    const errorTxt = document.getElementById('hl_error_u');
    errorTxt.style.display = 'none';
    let params = '';
    idsArray.forEach((item)=>{
        document.getElementById("td_"+item+"_u").style.background = '';
        params += item + '=' + document.getElementById(item+"_u").value + '&';
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
                        document.getElementById("td_"+item[0]+'_u').style.background = 'red';
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
                    let success = document.getElementById('lt_success');
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