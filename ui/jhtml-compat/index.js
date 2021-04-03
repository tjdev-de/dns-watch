*stop()
function dnswatch_search() {
	// activate spinner
	document.getElementById('loadani').style.visibility = 'visible';
	document.getElementById('loadani').style.display = 'block';

	// get domain
	var domain = document.getElementById('dnswatch-search').value.toLowerCase();

	// send api request
	var request = new XMLHttpRequest();
	request.open('POST', 'https://dns-watch.org/search', true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	request.onload = function() {
		if (request.status >= 200 && request.status < 400) {
			var response = JSON.parse(this.response);
			console.log(response);

			if (response['type'] == 'success') {   // success

				var table = '<table>\n';
				//// reference dns
				// status
				table += '<tr>\n';
				table += '<td class="status ref">\n';
				table += `<i data-feather="${ response["data"]["reference"]["found"] ? "check" : "x" }"></i>\n`;
				table += '</td>\n';
				// provider
				table += '<td class="icon">\n';
				table += `<i data-feather="${ response["data"]["reference"]["icon"] }"></i>\n`;
				table += '</td>\n'
				// name and address
				table += `<td class="name">${ response["data"]["reference"]["name"] }<small>${ response["data"]["reference"]["address"] }</small></td>\n`;
				// reference info
				table += '<td class="desc ref">reference</td>\n';
				// help button
				table += '<td class="help">\n';
				table += '<button><i data-feather="help-circle"></i></button>\n';
				table += '</td>\n';
				table += '</tr>\n'

				if (response['data']['reference']['found']) {   // only if reference confirms
					table += '<tr></tr>\n';   // spacer
					//// search rows
					response['data']['search'].forEach(i => {
						console.log(i);
						table += '<tr>\n';
						// status
						table += `<td class="status ${ i["matching"] ? "check" : "cross" }">\n`;
						table += `<i data-feather="${ i["matching"] ? "check" : "x" }"></i>\n`;
						table += '</td>\n';
						// provider icon
						table += '<td class="icon">\n';
						table += `<i data-feather="${ i["icon"] }"></i>\n`;
						table += '</td>\n';
						// name and address
						table += `<td class="name">${ i["name"] }<small>${ i["address"] }</small></td>\n`;
						// status info
						if (i['cause'] !== null) {
							if (i['cause'] == 'cuii') {
								table += '<td class="desc blocked">blocked by cuii</td>\n';
								table += '<td class="help">\n';
								table += '<button><i data-feather="help-circle"></i></button>\n';
								table += '</td>\n'
							}
						}
						table += '</tr>\n';
					});
				}
				table += '</table>';

			} else {   // no success

				var table = '<table>\n';
				table += '<tr>\n';
				// status
				table += '<td class="status cross">\n';
				table += '<i data-feather="x"></i>\n';
				table += '</td>\n';
				// provider icon
				table += '<td class="icon">\n';
				table += '<i data-feather="x"></i>\n';
				table += '</td>\n';
				// name and address
				table += `<td class="name">ERROR: ${ response["error"] }</td>\n`;
				table += '<td class="desc ref"></td>\n';
				table += '<td class="help"></td>\n';
				table += '</tr>\n';
				table += '</table>';

			}

			document.getElementById('results').innerHTML = table;
			feather.replace();
		}

		// deactivate spinner
		document.getElementById('loadani').style.visibility = 'hidden';
		document.getElementById('loadani').style.display = 'none';
	}

	request.send(`domain=${ window.btoa(domain) }`);
}



// add event triggers
document.addEventListener('DOMContentLoaded', () => {
	document.getElementById('dnswatch-search-submit').addEventListener('click', (event) => {
		dnswatch_search();
	});

	document.getElementById('dnswatch-search').addEventListener('keyup', (event) => {
		if (event.keyCode === 13) {
			event.preventDefault();
			dnswatch_search();
		}
	});
});
