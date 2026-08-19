<?php
/**
 * Tableau de bord des logs Agents IA
 */

require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = "Logs Agents IA";

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $pageTitle ?></title>



    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">


<style>

body{
    background:#f8fafc;
}


.logs-container{

    padding:30px;

}


.log-card{

    background:white;
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);

}


.log-success{

    border-left:5px solid #10b981;

}


.log-error{

    border-left:5px solid #ef4444;

}


.agent-badge{

    background:#e0e7ff;
    color:#3730a3;
    padding:5px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;

}


.status-success{

    color:#059669;
    font-weight:bold;

}


.status-error{

    color:#dc2626;
    font-weight:bold;

}


.json-box{

    background:#111827;
    color:#e5e7eb;
    padding:15px;
    border-radius:10px;
    font-size:13px;
    overflow:auto;

}


.empty{

    text-align:center;
    padding:50px;
    color:#9ca3af;

}


</style>


</head>


<body>


<div class="wrapper">


<?php include 'includes/sidebar.php'; ?>

<?php include 'includes/topbar.php'; ?>



<div class="main-content">


<div class="container-fluid logs-container">



<div class="d-flex justify-content-between align-items-center mb-4">


<h1>

<i class="fas fa-file-alt"></i>

Logs Agents IA

</h1>


<button class="btn btn-primary" onclick="loadLogs()">

<i class="fas fa-sync"></i>

Actualiser

</button>


</div>




<div class="card p-3 mb-4">


<label class="form-label">

Filtrer par agent

</label>


<select id="agentFilter" class="form-select">


<option value="">
Tous les agents
</option>


<option value="lead_analyst_agent">
Lead Analyst
</option>


<option value="inbox_agent">
Inbox Agent
</option>


<option value="campaign_agent">
Campaign Agent
</option>


</select>


</div>




<div id="logsContainer">


<div class="empty">

<i class="fas fa-spinner fa-spin fa-2x"></i>

<br>

Chargement des logs...

</div>


</div>



</div>


</div>


</div>





<script>


document.addEventListener(
"DOMContentLoaded",
function(){

    loadLogs();


    document
    .getElementById('agentFilter')
    .addEventListener(
        'change',
        loadLogs
    );


});





async function loadLogs(){


let agent =
document.getElementById('agentFilter').value;



let url =
"api/ai-agents.php?action=logs&limit=50";



if(agent){

    url += "&agent_name="+encodeURIComponent(agent);

}



let container =
document.getElementById('logsContainer');



try{


let response =
await fetch(url);



let data =
await response.json();



if(!data.success){

throw new Error(data.error || "Erreur API");

}



let logs =
data.data.logs || [];



if(logs.length===0){


container.innerHTML=`

<div class="empty">

<i class="fas fa-info-circle fa-3x"></i>

<h4>

Aucun log disponible

</h4>


</div>

`;

return;


}




container.innerHTML="";



logs.forEach(log=>{


let output="";


try{


output =
JSON.stringify(
    JSON.parse(log.output_data),
    null,
    2
);


}catch(e){


output =
log.output_data || "";

}




container.innerHTML += `



<div class="log-card ${log.status === 'success' ? 'log-success':'log-error'}">



<div class="d-flex justify-content-between mb-3">


<div>


<span class="agent-badge">

<i class="fas fa-robot"></i>

${log.agent_name}

</span>


<strong class="ms-3">

${log.action}

</strong>


</div>



<div>


${
log.status === 'success'

?

'<span class="status-success"><i class="fas fa-check"></i> Succès</span>'

:

'<span class="status-error"><i class="fas fa-times"></i> Erreur</span>'

}


</div>



</div>





<div class="mb-2">


<i class="fas fa-calendar"></i>

${log.created_at || ''}


</div>




<h6>

Résultat

</h6>


<pre class="json-box">

${escapeHtml(output)}

</pre>





${
log.error_message

?

`

<div class="alert alert-danger">

<strong>

Erreur :

</strong>

${log.error_message}

</div>

`

:

''

}




</div>


`;



});



}catch(error){


console.error(error);



container.innerHTML=`

<div class="alert alert-danger">

<i class="fas fa-exclamation-triangle"></i>

Erreur chargement logs :

${error.message}

</div>

`;


}



}





function escapeHtml(text){


return text

.replace(/&/g,"&amp;")

.replace(/</g,"&lt;")

.replace(/>/g,"&gt;")

.replace(/"/g,"&quot;")

.replace(/'/g,"&#039;");


}



</script>



</body>

</html>