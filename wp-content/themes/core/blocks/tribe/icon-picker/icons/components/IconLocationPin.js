import * as React from 'react';
const SvgIconLocationPin = ( props ) => (
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
			d="M52 21.09c0 11.782-20 32.836-20 32.836S12 32.874 12 21.09C12 9.01 22.336 2 32 2s20 7.01 20 19.09Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 28a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM16 47.434C8.746 48.88 4 51.282 4 54c0 4.418 12.536 8 28 8s28-3.582 28-8c0-2.718-4.746-5.12-12-6.566"
		/>
	</svg>
);
export default SvgIconLocationPin;
