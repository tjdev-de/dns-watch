function dnswatch_search() {
	// get domain
	var domain = document.getElementById('dnswatch-search').value.toLowerCase();

	// test if domain is invalid
	if (!is_valid_domain(domain)) {
		document.getElementById('results').innerHTML = '<table></table>';
		feather.replace();
		document.getElementById('invaliddomain').style.visibility = 'visible';
		document.getElementById('invaliddomain').style.display = 'block';
		return;
	}

	document.getElementById('invaliddomain').style.visibility = 'hidden';
	document.getElementById('invaliddomain').style.display = 'none';

	// activate spinner
	document.getElementById('loadani').style.visibility = 'visible';
	document.getElementById('loadani').style.display = 'block';

	// send api request
	var request = new XMLHttpRequest();
	request.open('POST', 'https://dns-watch.org/search', true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	request.onload = function() {
		if (request.status >= 200 && request.status < 400) {
			var response = JSON.parse(this.response);

			// TODO: CLEAN UP THIS MESS OF TABLE GENERATION CODE

			if (response['type'] == 'success') {   // success

				var table = '<table>\n';

				//// reference dns
				let first_ref = true;
				response['data']['reference'].forEach(i => {

					table += gen_tablerow({
						status_color: 'ref',
						status_icon:  get_status_icon(i['status']),
						dns_icon:     i['icon'],
						dns_name:     i['name'],
						dns_address:  i['address'],
						desc_color:   'ref',
						desc_text:    'reference',
						show_help:    first_ref
					})
					first_ref = false;

				});

				table += '</table>';

				// randomized response message
				if (response['data']['randomized_response']) {
					table += '<small class="random">This domain uses randomized responses. <button>Learn more</button></small>\n';
				}

				if (response['data']['found']) {   // only if reference confirms
					table += '<table><tr></tr>\n';   // spacer
					//// search rows
					let causes = [];
					response['data']['search'].forEach(i => {

						table += gen_tablerow({
							status_color: get_status_color(i['status']),
							status_icon:  get_status_icon(i['status']),
							dns_icon:     i['icon'],
							dns_name:     i['name'],
							dns_address:  i['address'],
							desc_color:   i['cause'] !== null ? 'blocked': '',
							desc_text:    i['cause'] == 'cuii' ? 'reference' : '',
							show_help:    i['cause'] !== null && !causes.includes(i['cause'])
						})

						if (i['cause'] !== null) causes.push(i['cause'])

					});
				}
				table += '</table>';

			} else {   // no success

				if (response['error'] == 'malformed_domain') {   // invalid domain - should not happen, but just in case i guess
					var table = '<table></table>';
					document.getElementById('results').innerHTML = '<table></table>';
					feather.replace();
					document.getElementById('invaliddomain').style.visibility = 'visible';
					document.getElementById('invaliddomain').style.display = 'block';
				} else {
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



function is_valid_domain(str) {
	let regex = /^([a-z0-9\-]{1,64}\.){1,16}[a-z0-9]{2,}$/;
	return regex.test(str);
}



function gen_tablerow(options) {
	return `
<tr>
	<td class="status ${ options.status_color }"><i data-feather="${ options.status_icon }"></i></td>
	<td class="icon"><img class="uncolored-svg" src="${ options.dns_icon }"></td>
	<td class="name">${ options.dns_name }<small>${ options.dns_address }</small></td>
	<td class="desc ${ options.desc_color }">${ options.desc_text }</td>
	${ options.show_help ? '<td class="help"><button><i data-feather="help-circle"></i></button></td>' : '' }
</tr>
`
}

function get_status_color(status) {
	if (status === true)  return 'check'
	if (status === false) return 'cross'
	                      return 'offline'
}

function get_status_icon(status) {
	if (status === true)  return 'check'
	if (status === false) return 'x'
	                      return 'wifi-off'
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
