import * as React from 'react';
const SvgIconClipboardCheck = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m24 38 6 6 12-12M38 8a6 6 0 1 0-12 0h-6v8h24V8z"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M50 10h6v52H8V10h6"
		/>
	</svg>
);
export default SvgIconClipboardCheck;
