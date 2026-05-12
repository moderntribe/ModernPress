import * as React from 'react';
const SvgIconTelescope = ( props ) => (
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
			d="m26 29.572-8.998 2.61-4.458-15.366L56.728 4l4.458 15.366-24.364 7.068"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M10.028 31.42 5.962 32.6 2.974 22.298l4.008-1.162M32 36a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM29.028 35.202 16 58M34.972 35.202 48 58"
		/>
	</svg>
);
export default SvgIconTelescope;
