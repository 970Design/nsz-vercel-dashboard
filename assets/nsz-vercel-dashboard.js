
function setDeployButtonLoading(loading = true) {
    const button = document.querySelector('.nsz-design-vercel-header .start-vercel-deploy');
    if (!button) return;

    if (loading) {
        button.classList.add('loading');
        button.disabled = true;
    } else {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

let deploymentPollingInterval = null;

function setCancelButtonLoading(deploymentId, loading = true) {
    const button = document.querySelector(`.cancel-vercel-deploy[data-id="${deploymentId}"]`);

    if (!button) return;

    if (loading) {
        button.classList.add('loading');
        button.disabled = true;
    } else {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

function cancelVercelDeploy(id) {
    setCancelButtonLoading(id, true);

    let url = 'https://api.vercel.com/v12/deployments/'+id+'/cancel';
    let xhr = new XMLHttpRequest();
    xhr.open('PATCH', url, true);
    xhr.setRequestHeader('Authorization', 'Bearer ' + nsz_vercel_dashboard_admin_js.api_token);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
        } else if (xhr.readyState === 4) {
            let errorMessage = 'Error cancelling deployment';

            if (xhr.status === 401) {
                errorMessage = 'Unauthorized - check your API token';
            } else if (xhr.status === 404) {
                errorMessage = 'Deployment not found or already completed';
            } else if (xhr.status === 403) {
                errorMessage = 'Permission denied - cannot cancel this deployment';
            } else if (xhr.status >= 500) {
                errorMessage = 'Server error - please try again later';
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error && response.error.message) {
                        errorMessage = response.error.message;
                        console.error(errorMessage);
                    }
                } catch (e) {
                    console.error(e, errorMessage);
                }
            }
            console.error(errorMessage);
            setCancelButtonLoading(id, false);
        }
    };

    xhr.onerror = function() {
        console.error('Network error cancelling deployment');
        alert('Network error - check your connection and try again');
        setCancelButtonLoading(id, false);
    };

    xhr.send();
}

document.addEventListener('DOMContentLoaded', function() {
    const deploymentsList = document.getElementById('nsz-design-vercel-deployments-list');
    if (deploymentsList) {
        deploymentsList.addEventListener('click', function(e) {
            const cancelButton = e.target.closest('.cancel-vercel-deploy');
            if (cancelButton) {
                e.preventDefault();
                const id = cancelButton.getAttribute('data-id');
                cancelVercelDeploy(id);
            }
        });
    }

    const startButton = document.querySelector('.start-vercel-deploy');
    if (startButton) {
        startButton.addEventListener('click', function (e) {
            e.preventDefault();
            startVercelDeploy();
        });
    }
    startDeploymentPolling();
});


function refreshDeployments() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', nsz_vercel_dashboard_admin_js.ajax_url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update deployments using differential updates
                    updateDeploymentsList(response.data.deployments);

                    // Reset deploy button loading state when deployments are updated
                    setDeployButtonLoading(false);

                    // Check if we should stop polling
                    checkPolling();
                }
            } catch (e) {
                console.log('Error parsing AJAX response:', e);
            }
        }
    };

    const formData = 'action=refresh_vercel_deployments&nonce=' + nsz_vercel_dashboard_admin_js.nonce;
    xhr.send(formData);
}


function updateDeploymentsList(deployments) {
    const deploymentsList = document.getElementById('nsz-design-vercel-deployments-list');
    if (!deploymentsList) return;

    const existingItems = deploymentsList.querySelectorAll('[data-deployment-id]');
    const existingIds = Array.from(existingItems).map(item => item.getAttribute('data-deployment-id'));
    const newIds = deployments.map(d => d.uid);

    existingIds.forEach(id => {
        if (!newIds.includes(id)) {
            const item = deploymentsList.querySelector(`[data-deployment-id="${id}"]`);
            if (item) item.remove();
        }
    });

    deploymentsList.innerHTML = '';

    deployments.forEach(deployment => {
        const existingItem = document.querySelector(`[data-deployment-id="${deployment.uid}"]`);

        if (existingItem) {
            updateDeploymentItem(existingItem, deployment);
            deploymentsList.appendChild(existingItem);
        } else {
            const newItem = createDeploymentItem(deployment);
            deploymentsList.appendChild(newItem);
        }
    });
}

function createDeploymentItem(deployment) {
    const li = document.createElement('li');
    li.setAttribute('data-deployment-id', deployment.uid);

    let html = `<strong class='nsz-vercel-deployment-id'>${deployment.shortId}</strong>`;

    html += `<div class='nsz-vercel-deployment-status'>`;
    html += `<span class='nsz-vercel-state nsz-vercel-state-${deployment.state.toLowerCase()}'>${deployment.state.charAt(0) + deployment.state.slice(1).toLowerCase()}</span>`;

    if (deployment.buildTime) {
        html += deployment.buildTime;
    }

    html += ` (${deployment.formattedTime})`;
    html += `</div>`;

    if (deployment.isActive) {
        html += `
        <button class="cancel-vercel-deploy button button-cancel" data-id="${deployment.uid}">
        <span>Cancel Deployment</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle fill="none" stroke-opacity="1" stroke="#2271B1" stroke-width=".5" cx="100" cy="100" r="0"><animate attributeName="r" calcMode="spline" dur="2" values="1;80" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-width" calcMode="spline" dur="2" values="0;25" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-opacity" calcMode="spline" dur="2" values="1;0" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate></circle></svg>
        </button>`;
    }

    li.innerHTML = html;
    return li;
}

function updateDeploymentItem(element, deployment) {

    const idElement = element.querySelector('.nsz-vercel-deployment-id');
    if (idElement && idElement.textContent !== deployment.shortId) {
        idElement.textContent = deployment.shortId;
    }

    const stateElement = element.querySelector('.nsz-vercel-state');
    if (stateElement) {
        const newStateClass = `nsz-vercel-state nsz-vercel-state-${deployment.state.toLowerCase()}`;
        const newStateText = deployment.state.charAt(0) + deployment.state.slice(1).toLowerCase();

        if (stateElement.className !== newStateClass) {
            stateElement.className = newStateClass;
        }
        if (stateElement.textContent !== newStateText) {
            stateElement.textContent = newStateText;
        }
    }

    const statusElement = element.querySelector('.nsz-vercel-deployment-status');
    if (statusElement) {
        let newContent = `<span class='nsz-vercel-state nsz-vercel-state-${deployment.state.toLowerCase()}'>${deployment.state.charAt(0) + deployment.state.slice(1).toLowerCase()}</span>`;

        if (deployment.buildTime) {
            newContent += deployment.buildTime;
        }

        newContent += ` (${deployment.formattedTime})`;

        if (statusElement.innerHTML !== newContent) {
            statusElement.innerHTML = newContent;
        }
    }

    const existingButton = element.querySelector('.cancel-vercel-deploy');
    if (deployment.isActive && !existingButton) {
        // Add cancel button
        const buttonHtml = `
        <button class="cancel-vercel-deploy button button-cancel" data-id="${deployment.uid}">
        <span>Cancel Deployment</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle fill="none" stroke-opacity="1" stroke="#2271B1" stroke-width=".5" cx="100" cy="100" r="0"><animate attributeName="r" calcMode="spline" dur="2" values="1;80" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-width" calcMode="spline" dur="2" values="0;25" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-opacity" calcMode="spline" dur="2" values="1;0" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate></circle></svg>
        </button>`;
        element.insertAdjacentHTML('beforeend', buttonHtml);
    } else if (!deployment.isActive && existingButton) {
        existingButton.remove();
    }
}

// check if polling should stop
function checkPolling() {
    const deploymentsList = document.getElementById('nsz-design-vercel-deployments-list');
    if (!deploymentsList) return;

    // Check if there are any building or queued deployments
    const buildingDeployments = deploymentsList.querySelectorAll('.nsz-vercel-state-building, .nsz-vercel-state-queued');

    // If no active deployments, stop polling
    if (buildingDeployments.length === 0) {
        stopDeploymentPolling();
    }
}

function stopDeploymentPolling() {
    if (deploymentPollingInterval) {
        clearInterval(deploymentPollingInterval);
        deploymentPollingInterval = null;
    }
}

function startDeploymentPolling() {
    stopDeploymentPolling();

    // Refresh immediately when page loads
    refreshDeployments();

    // Then refresh every second for efficient updates
    deploymentPollingInterval = setInterval(function() {
        refreshDeployments();
    }, 5000);
}


function startVercelDeploy() {
    setDeployButtonLoading(true);

    let url = 'https://api.vercel.com/v13/deployments?forceNew=1&skipAutoDetectionConfirmation1';
    let xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Authorization', 'Bearer ' + nsz_vercel_dashboard_admin_js.api_token);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            startDeploymentPolling();

            setTimeout(() => {
                setDeployButtonLoading(false);
            }, 1000);
        } else if (xhr.readyState === 4) {
            setDeployButtonLoading(false);
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