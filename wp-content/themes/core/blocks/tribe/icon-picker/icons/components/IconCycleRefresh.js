import * as React from 'react';
const SvgIconCycleRefresh = ( props ) => (
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
			d="M58.764 18.434A30 30 0 0 0 2 32"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m56.766 2.44 2 16-16-2"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M5.236 45.566A30 30 0 0 0 62 32"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="m7.234 61.56-2-16 16 2"
		/>
	</svg>
);
export default SvgIconCycleRefresh;
