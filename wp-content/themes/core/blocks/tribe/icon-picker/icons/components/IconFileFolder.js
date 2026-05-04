import * as React from 'react';
const SvgIconFileFolder = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M2 24h60"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 58H8a6 6 0 0 1-6-6V4h22l6 10h32v38a6 6 0 0 1-6 6Z"
		/>
	</svg>
);
export default SvgIconFileFolder;
