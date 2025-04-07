
function cancelVercelDeploy(id) {
    let url = 'https://api.vercel.com/v12/deployments/'+id+'/cancel';
    let xhr = new XMLHttpRequest();
    xhr.open('PATCH', url, true);
    xhr.setRequestHeader('Authorization', 'Bearer ' + nsz_vercel_dashboard_admin_js.api_token);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            alert('Deploy cancelled');
            location.reload();
        } else if (xhr.readyState === 4) {
            alert('Error cancelling deploy');
        }
    };
    xhr.send();
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.cancel-vercel-deploy').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      let id = this.getAttribute('data-id');
      cancelVercelDeploy(id);
    });
  });

  document.querySelectorAll('.start-vercel-deploy').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      startVercelDeploy();
    });
  });
});


function startVercelDeploy() {
    let url = 'https://api.vercel.com/v13/deployments?forceNew=1&skipAutoDetectionConfirmation1';
    let xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Authorization', 'Bearer ' + nsz_vercel_dashboard_admin_js.api_token);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            alert('Deploy started');
            location.reload();
        } else if (xhr.readyState === 4) {
            alert('Error starting deploy');
            console.log(xhr.responseText);
        }
    };
    let data = JSON.stringify({
        "name": 'manual-deployment',
        "project": nsz_vercel_dashboard_admin_js.project_id,
        "gitSource": {
            "type": "github",
            "repo": nsz_vercel_dashboard_admin_js.git_repo,
            "org": nsz_vercel_dashboard_admin_js.git_org,
            "ref": nsz_vercel_dashboard_admin_js.git_branch
        },
        "target": "production"
    });
    xhr.send(data);
}