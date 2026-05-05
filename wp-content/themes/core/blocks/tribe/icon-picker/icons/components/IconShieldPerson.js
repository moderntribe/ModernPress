import * as React from 'react';
const SvgIconShieldPerson = ( props ) => (
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
			d="M15.864 50c3.64-4.85 9.512-8 16.136-8s12.496 3.15 16.136 8M32 35a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M56 34c0 18-24 26-24 26S8 52 8 34V10l24-6 24 6z"
		/>
	</svg>
);
export default SvgIconShieldPerson;
