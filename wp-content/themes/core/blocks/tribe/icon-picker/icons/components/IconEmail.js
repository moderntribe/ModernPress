import * as React from 'react';
const SvgIconEmail = ( props ) => (
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
			d="m4 10 28 22 28-22"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 8H8a6 6 0 0 0-6 6v36a6 6 0 0 0 6 6h48a6 6 0 0 0 6-6V14a6 6 0 0 0-6-6Z"
		/>
	</svg>
);
export default SvgIconEmail;
