import * as React from 'react';
const SvgIconEyeVisible = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path fill="currentColor" d="M32 34a6 6 0 1 0 0-12 6 6 0 0 0 0 12" />
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M2.818 34.364a3.87 3.87 0 0 1-.016-4.74C6.844 24.324 17.772 12 32 12c14.04 0 25.072 12.316 29.17 17.62a3.874 3.874 0 0 1 0 4.76C57.072 39.684 46.04 52 32 52S6.906 39.656 2.818 34.364Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 44c8.837 0 16-7.163 16-16s-7.163-16-16-16-16 7.163-16 16 7.163 16 16 16Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 34a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"
		/>
	</svg>
);
export default SvgIconEyeVisible;
