import * as React from 'react';
const SvgIconTargetBullseye = ( props ) => (
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
			d="M49.242 31.992c.494 1.92.758 3.934.758 6.008 0 13.254-10.746 24-24 24S2 51.254 2 38s10.746-24 24-24c2.074 0 4.088.264 6.006.758"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M26 52c7.732 0 14-6.268 14-14s-6.268-14-14-14-14 6.268-14 14 6.268 14 14 14Z"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M26 38 58 6"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M50 14 48 4l-8 8 2 10 10 2 8-8z"
		/>
	</svg>
);
export default SvgIconTargetBullseye;
