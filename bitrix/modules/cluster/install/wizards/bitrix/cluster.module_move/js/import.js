// eslint-disable-next-line no-unused-vars
function DropTables()
{
	BX.ajax.post(
		`${path}/scripts/drop.php`,
		{
			sessid: sessid,
			to_node_id: toNodeId,
			module: module,
			lang: LANG,
		},
		(data) => {
			const obContainer = document.getElementById('output');
			if (obContainer)
			{
				obContainer.innerHTML = data;
			}
		},
	);
}

// eslint-disable-next-line no-unused-vars
function MoveTables(STEP)
{
	let step = STEP;

	if (step == null)
	{
		step = 1;
	}

	if (BX.Type.isObject(step))
	{
		step = 1;
	}

	BX.ajax.post(
		`${path}/scripts/move.php`,
		{
			sessid: sessid,
			from_node_id: fromNodeId,
			to_node_id: toNodeId,
			module: module,
			status: nodeStatus,
			STEP: step,
			lang: LANG,
		},
		(data) => {
			const obContainer = document.getElementById('output');
			if (obContainer)
			{
				obContainer.innerHTML = data;
			}
		},
	);
}

// eslint-disable-next-line no-unused-vars
function RunError()
{
	const obErrorMessage = document.getElementById('error_message');
	if (obErrorMessage)
	{
		BX.Dom.style(obErrorMessage, 'display', 'inline');
	}
}

// eslint-disable-next-line no-unused-vars
function RunAgain()
{
	const obOut = document.getElementById('output');
	const obErrorMessage = document.getElementById('error_message');

	obOut.innerHTML = '';
	BX.Dom.style(obErrorMessage, 'display', 'none');
	Run(1);
}

// eslint-disable-next-line no-unused-vars
function DisableButton(e)
{
	const obNextButton = document.forms[formID][nextButtonID];
	obNextButton.disabled = true;
}

// eslint-disable-next-line no-unused-vars
function EnableButton()
{
	const obNextButton = document.forms[formID][nextButtonID];
	obNextButton.disabled = false;
}
