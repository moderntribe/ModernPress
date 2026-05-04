import * as React from 'react';
const SvgIconTimeline = ( props ) => (
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
			d="M32 2v60M62 6H46v16h16zM38 14h-6M62 42H46v16h16zM38 50h-6M2 40h16V24H2zM26 32h6"
		/>
	</svg>
);
export default SvgIconTimeline;
