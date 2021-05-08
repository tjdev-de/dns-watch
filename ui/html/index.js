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
	request.open('POST', 'https://dns-watch.org/lookup', true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	request.onload = function() {
		if (request.status >= 200 && request.status < 400) {
			var response = JSON.parse(this.response);

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
						show_help:    first_ref,
						help_type:    'reference'
					})
					first_ref = false;

				});

				table += '</table>';

				// randomized response message
				if (response['data']['randomized_response']) {
					table += '<small class="random">This domain uses randomized responses. <button onclick="show_modal(\'random\')">Learn more</button></small>\n';
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
							desc_text:    get_blocked_text(i['cause']),
							show_help:    i['cause'] !== null && !causes.includes(i['cause']),
							help_type:    'blocked-' + i['cause']
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

					table += gen_tablerow({
						status_color: 'cross',
						status_icon:  'x',
						dns_icon:     'x',
						dns_name:     `ERROR: ${ response['error'] }`,
						dns_address:  '',
						desc_color:   'ref',
						desc_text:    '',
						show_help:    false,
						help_type:    ''
					})

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
	${ options.show_help ? '<td class="help"><button onclick="show_modal(\''+options.help_type+'\')"><i data-feather="help-circle"></i></button></td>' : '' }
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

function get_blocked_text(cause) {
	if (cause == 'cuii') return 'blocked by cuii'
	if (cause !== null)  return `bocked: ${cause}`
	                     return ''
}



function show_modal(type) {
	let msg;
	switch (type) {
		case 'search':
			msg = '<b>dns-watch.org</b> is a service that allows you to see how (mainly German) Internet Service Providers (ISPs) deal with Internet censorship. <a href="/why">More Information</a><br>In order to test a website for availability, enter the domain into the search bar and press Enter.<br><span>Try out these examples if you are unsure: gnu.org, wikipedia.org, s.to</span>';
			break;
		case 'reference':
			msg = 'In order to validate the DNS responses, we use <b>references</b>.<br>These references are from trustworthy DNS providers, such as Cloudflare or UncensoredDNS.<br>The responses of the DNS providers that should be tested (located in the bottom table) will be compared to the results from the references.'
			break;
		case 'blocked-cuii':
			msg = 'This ISP (and all others with the same error message) blocked the request on behalf of <b>CUII</b>.<br>CUII is a German institution that censors parts of the Internet because of so-called copyright claims.<br>For more information about the CUII, read our <a href="/why">Why?</a> page.'
			break;
		case 'random':
			msg = 'This domain uses <b>randomized responses</b>.<br>Some websites return different IP addresses for load balancing.<br>Therefore, dns-watch.org can\'t compare the results of these DNS providers and will only check for common blocking patterns.'
			break;
		default:
			msg = `It seems like something happend that shouldn\'t have happened!<br><span>Dialog message type: ${ type }</span>`
			break;
	}

	document.getElementById('modal-message').innerHTML = msg
	document.getElementById('modal').style.display = 'block'
}

function close_modal() {
	document.getElementById('modal').style.display = 'none'
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

	window.onclick = function (event) {
		if (event.target == document.getElementById('modal'))
			close_modal()
	}
});
