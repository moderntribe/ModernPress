import * as React from 'react';
const SvgIconCircleExclamationMark = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			fill="currentColor"
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 48a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 60c15.464 0 28-12.536 28-28S47.464 4 32 4 4 16.536 4 32s12.536 28 28 28ZM32 16v22"
		/>
	</svg>
);
export default SvgIconCircleExclamationMark;
