import * as React from 'react';
const SvgIconFlag = ( props ) => (
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
			d="M4.6 16.4 30.2 61M57.4 26.6C47 36 34.2 24.4 26.6 38.2L14.2 17C22 3.4 34.8 15 45 5.4z"
		/>
	</svg>
);
export default SvgIconFlag;
