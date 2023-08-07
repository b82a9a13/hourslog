const idsArray = [
    'date',
    'activity',
    'whatlink',
    'impact',
    'duration'
];
$('#hourslog_form')[0].addEventListener('submit', (e)=>{
    e.preventDefault();
    const errorTxt = $('#hl_error')[0];
    errorTxt.style.display = 'none';
    let params = '';
    idsArray.forEach(function(item){
        $(`#td_${item}`)[0].style.background = '';
        params += item + '=' + $(`#${item}`)[0].value + '&';
    });
    params = params.slice(0, -1);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/submit_log_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['error']){
                errorTxt.innerText = 'Invalid values: ';
                text['error'].forEach(function(item){
                    if(idsArray.includes(item[0])){
                        $(`#td_${item[0]}`)[0].style.background = 'red';
                        errorTxt.innerText += item[1] + '|';
                    }
                });
                errorTxt.style.display = 'block';
            } else {
                if(text['return']){
                    clear_fields();
                    update_table();
                    refresh_it();
                    refresh_bar();
                } else {
                    errorTxt.innerText = 'Creation error.';
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
function clear_fields(){
    idsArray.forEach(function(item){
        $(`#td_${item}`)[0].style.background = '';
        $(`#${item}`)[0].value = '';
    });
}
function update_table(){
    const errorTxt = $('#ut_error')[0];
    errorTxt.style.display = 'none';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/update_table_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['return']){
                let tbody = $('#logs_table_tbody')[0];
                tbody.innerHTML = '';
                text['return'].forEach(function(item){
                    let tr = document.createElement('tr');
                    let td = document.createElement('td');
                    let button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'logs-btns btn';
                    button.setAttribute('onclick', 'clicked_log_id('+item[1]+')');
                    button.disabled = true;
                    button.innerText = item[0];
                    td.appendChild(button);
                    tr.appendChild(td);
                    let int = 2;
                    while(int < 7){
                        td = document.createElement('td');
                        td.innerText = item[int];
                        tr.appendChild(td);
                        int++;
                    }
                    td = document.createElement('td');
                    let atag = document.createElement('a');
                    atag.href = './../../user/profile.php?id='+item[7];
                    atag.target = '_blank';
                    atag.innerText = item[8];
                    td.appendChild(atag);
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                });
            } else {
                errorTxt.innerText = 'Data loading error, when updating table';
                errorTxt.style.display = 'block';
            }
        } else {
            errorTxt.innerText = 'Connection error, when updating table';
            errorTxt.style.display = 'block';
        }
    }
    xhr.send();
}
let logType = 'none';
function reset_log_ids(){
    update_delete();
    $('#lt_success')[0].style.display = 'none';
    $('#lt_error')[0].style.display = 'none';
    const ids = document.querySelectorAll('.logs-btns');
    ids.forEach(function(item){
        item.className = 'logs-btns btn';
        item.disabled = true;
    });
    logType = 'none';
}
function update_log_ids(){
    $('#lt_success')[0].style.display = 'none';
    $('#lt_error')[0].style.display = 'none';
    const ids = document.querySelectorAll('.logs-btns');
    ids.forEach(function(item){
        item.className = 'logs-btns btn btn-primary';
        item.disabled = false;
    });
    logType = 'update';
}
function clicked_log_id(id){
    $('#lt_success')[0].style.display = 'none';
    const errorTxt = $('#lt_error')[0];
    errorTxt.style.display = 'none';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/'+logType+'_log_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    if(logType == 'update'){
        xhr.onload = function(){
            if(this.status == 200){
                const text = JSON.parse(this.responseText);
                if(text['error']){
                    errorTxt.innerText = text['error'];
                    errorTxt.style.display = 'block';
                } else{
                    if(text['return']){
                        const urDiv = $('#update_record_div')[0];
                        urDiv.innerHTML = text['return'];
                        const script = document.createElement('script');
                        script.src = './amd/min/update_log_learn.min.js';
                        urDiv.appendChild(script);
                        urDiv.scrollIntoView();
                    } else{
                        errorTxt.innerText = 'Loading error';
                        errorTxt.style.display = 'block';
                    }
                }
            } else {
                errorTxt.innerText = 'Connection error';
                errorTxt.style.display = 'block';
            }
        }
        xhr.send(`id=${id}`);
    }
}
function refresh_it(){
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/refresh_it_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['return']){
                $('#it_total_left')[0].innerText = text['return'];
            }
        }
    }
    xhr.send();
}
refresh_bar();
function refresh_bar(){
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/refresh_bar_learn.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['return']){
                const progress = text['return'][0];
                const expected = text['return'][1];
                $('#otjh_prog_progress_p')[0].innerText = `: ${progress}%`;
                $('#otjh_prog_expected_p')[0].innerText = `: ${expected}%`;
                $('#otjh_prog_incomplete_p')[0].innerText = `: ${100 - progress}%`;
                const progressbar = $('#progressbar')[0];
                if(progress >= expected){
                    progressbar.style = `width: ${progress}%; height: 25px; background-color: green;`;
                } else {
                    progressbar.style = `width: ${progress}%; height: 25px; background-color: green;`;
                    $('#expectedbar')[0].style = `width: ${expected - progress}%; height: 25px; background-color: orange;`
                }
            }
        }
    }
    xhr.send();
}
function update_delete(){
    $('#update_record_div')[0].innerHTML = '';
}